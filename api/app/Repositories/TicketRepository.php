<?php

namespace App\Repositories;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Interfaces\TicketRepositoryInterface;
use App\Models\Ticket;
use App\Services\WhatsAppNotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\User;

class TicketRepository implements TicketRepositoryInterface
{
    public function getAll(
        ?string $search,
        ?int $limit,
        bool $execute,
        ?string $status = null,
        ?string $priority = null,
        ?int $branchId = null,
        ?int $assignedTo = null,
        ?string $startDate = null,
        ?string $endDate = null
    ) {
        $user = Auth::user();
        /** @var \App\Models\User|null $user */

        $query = Ticket::with(['user', 'branch', 'assignedStaff', 'workOrder'])
            ->withCount('replies')
            ->orderBy('created_at', 'desc')
            ->where(function ($query) use ($search) {
                if ($search) {
                    $query->where('code', 'like', '%' . $search . '%')
                        ->orWhere('title', 'like', '%' . $search . '%')
                        ->orWhere('description', 'like', '%' . $search . '%');
                }
            });

        // Apply filters
        if ($status) {
            $query->where('status', $status);
        }

        if ($priority) {
            $query->where('priority', $priority);
        }

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        if ($assignedTo) {
            $query->whereHas('assignedStaff', function ($staffQuery) use ($assignedTo) {
                $staffQuery->where('user_id', $assignedTo);
            });
        }

        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        // Role-based visibility
        if ($user && ($user->hasRole('admin') || $user->hasRole('superadmin'))) {
            // admins and superadmins can see all tickets
        } elseif ($user && $user->hasRole('staff')) {
            $query->where(function ($q) use ($user) {
                $q->whereHas('assignedStaff', function ($staffQuery) use ($user) {
                    $staffQuery->where('user_id', $user->id);
                })
                    ->orWhere('branch_id', $user->branch_id);
            });
        } else {
            // default: regular user can only see own tickets
            if ($user) {
                $query->where('user_id', $user->id);
            } else {
                // no auth user, return empty
                $query->whereRaw('1=0');
            }
        }

        if ($limit) {
            $query->take($limit);
        }

        if ($execute) {
            return $query->get();
        }

        return $query;
    }

    public function getAllPaginated(
        ?string $search,
        ?int $rowPerPage,
        ?string $status = null,
        ?string $priority = null,
        ?int $branchId = null,
        ?int $assignedTo = null,
        ?string $startDate = null,
        ?string $endDate = null
    ) {
        $query = $this->getAll(
            $search,
            null,
            false,
            $status,
            $priority,
            $branchId,
            $assignedTo,
            $startDate,
            $endDate
        );

        return $query->paginate($rowPerPage);
    }

    public function getByCode(
        string $code
    ) {
        $user = Auth::user();
        /** @var \App\Models\User|null $user */

        $query = Ticket::with(['user', 'branch', 'assignedStaff', 'workOrder'])
            ->withCount('replies')
            ->where('code', $code);

        if ($user && $user->hasRole('admin')) {
            // admins can access any ticket
        } elseif ($user && $user->hasRole('staff')) {
            $query->whereHas('assignedStaff', function ($staffQuery) use ($user) {
                $staffQuery->where('user_id', $user->id);
            });
        } else {
            if ($user) {
                $query->where('user_id', $user->id);
            } else {
                $query->whereRaw('1=0');
            }
        }

        return $query->firstOrFail();
    }

    public function getById(
        string $id
    ) {
        $user = Auth::user();
        /** @var \App\Models\User|null $user */

        $query = Ticket::with(['user', 'branch', 'assignedStaff', 'workOrder'])
            ->withCount('replies')
            ->where('id', $id);

        if ($user && $user->hasRole('admin')) {
            // admins can access any ticket
        } elseif ($user && $user->hasRole('staff')) {
            $query->where(function ($q) use ($user) {
                $q->whereHas('assignedStaff', function ($staffQuery) use ($user) {
                    $staffQuery->where('user_id', $user->id);
                })
                    ->orWhere('branch_id', $user->branch_id);
            });
        } else {
            if ($user) {
                $query->where('user_id', $user->id);
            } else {
                $query->whereRaw('1=0');
            }
        }

        return $query->firstOrFail();
    }

    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            // Validate staff availability in branch
            if (isset($data['branch_id']) && $data['branch_id']) {
                $hasStaff = User::role('staff')->where('branch_id', $data['branch_id'])->exists();
                if (!$hasStaff) {
                    throw new \Exception('Cabang ini belum memiliki staff teknisi, tidak dapat membuat tiket.');
                }
            }

            $ticket = new Ticket();
            $ticket->user_id = $data['user_id'];
            $ticket->code = $this->generateTicketCode($data['branch_id'] ?? null);
            $ticket->title = $data['title'];
            $ticket->description = $data['description'];
            $ticket->status = $data['status'] ?? TicketStatus::OPEN;
            $ticket->priority = $data['priority'] ?? TicketPriority::LOW;
            $ticket->branch_id = $data['branch_id'] ?? null;
            $ticket->completed_at = $data['completed_at'] ?? null;
            $ticket->save();

            // Handle assigned staff
            if (isset($data['assigned_staff']) && is_array($data['assigned_staff'])) {
                $ticket->assignedStaff()->sync($data['assigned_staff']);
            }

            $ticket = $ticket->load(['user', 'branch', 'assignedStaff'])->loadCount('replies');

            // Send WhatsApp notification for new ticket
            try {
                $whatsappService = app(WhatsAppNotificationService::class);
                $whatsappService->sendNewTicketNotification($ticket);
            } catch (\Exception $e) {
                // Log error but don't fail the ticket creation
                Log::error('Failed to send WhatsApp notification for new ticket', [
                    'ticket_id' => $ticket->id,
                    'error' => $e->getMessage()
                ]);
            }

            return $ticket;
        });
    }

    public function update(string $id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $ticket = $this->getById($id);

            // Validate Closing Rule
            if (isset($data['status']) && $data['status'] === TicketStatus::CLOSED->value && $ticket->status !== TicketStatus::CLOSED) {
                if (!isset($data['system_auto_close']) || !$data['system_auto_close']) {
                    $user = Auth::user();
                    $isCreator = $user && $user->id === $ticket->user_id;
                    $isAdmin = $user && $user->hasRole('admin');

                    if (!$isCreator && !$isAdmin) {
                        throw new \Exception('Tiket hanya boleh di-close oleh pembuat tiket atau admin.');
                    }
                }
            }

            $oldStatus = $ticket->status; // Store old status for notification
            $oldAssignedStaff = $ticket->assignedStaff->pluck('id')->toArray(); // Store old assigned staff

            $user = Auth::user();
            /** @var \App\Models\User|null $user */

            if ($user && $user->hasRole('staff')) {
                // Staff can ONLY update status
                $status = $data['status'] ?? $ticket->status;
                $update = ['status' => $status];
                if ($status === TicketStatus::RESOLVED && !$ticket->completed_at) {
                    $update['completed_at'] = now();
                }
                $ticket->fill($update)->save();
            } else {
                $update = [
                    'user_id' => $data['user_id'] ?? $ticket->user_id,
                    'title' => $data['title'] ?? $ticket->title,
                    'description' => $data['description'] ?? $ticket->description,
                    'status' => $data['status'] ?? $ticket->status,
                    'priority' => $data['priority'] ?? $ticket->priority,
                    'branch_id' => $data['branch_id'] ?? $ticket->branch_id,
                ];

                $newStatus = $data['status'] ?? $ticket->status;
                if ($newStatus == TicketStatus::RESOLVED && !$ticket->completed_at) {
                    $update['completed_at'] = now();
                } else {
                    $update['completed_at'] = $data['completed_at'] ?? $ticket->completed_at;
                }

                $ticket->fill($update)->save();

                // Handle assigned staff
                if (isset($data['assigned_staff']) && is_array($data['assigned_staff'])) {
                    $ticket->assignedStaff()->sync($data['assigned_staff']);
                }
            }

            $ticket = $ticket->load(['user', 'branch', 'assignedStaff'])->loadCount('replies');

            // Send WhatsApp notification for status update if status changed
            if ($oldStatus !== $ticket->status) {
                try {
                    $whatsappService = app(WhatsAppNotificationService::class);
                    $whatsappService->sendTicketStatusUpdateNotification($ticket, $oldStatus->value);
                } catch (\Exception $e) {
                    // Log error but don't fail the ticket update
                    Log::error('Failed to send WhatsApp notification for status update', [
                        'ticket_id' => $ticket->id,
                        'old_status' => $oldStatus,
                        'new_status' => $ticket->status,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Send WhatsApp notification for staff assignment changes
            $newAssignedStaff = $ticket->assignedStaff->pluck('id')->toArray();
            if ($oldAssignedStaff !== $newAssignedStaff) {
                try {
                    $whatsappService = app(WhatsAppNotificationService::class);
                    $whatsappService->sendTicketAssignmentNotification($ticket, $oldAssignedStaff);
                } catch (\Exception $e) {
                    // Log error but don't fail the ticket update
                    Log::error('Failed to send WhatsApp notification for staff assignment', [
                        'ticket_id' => $ticket->id,
                        'old_assigned_staff' => $oldAssignedStaff,
                        'new_assigned_staff' => $newAssignedStaff,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            return $ticket;
        });
    }

    public function delete(string $id)
    {
        return DB::transaction(function () use ($id) {
            $ticket = $this->getById($id);

            $ticket->delete();

            return $ticket;
        });
    }

    public function assignStaff(string $id, array $staffIds)
    {
        return DB::transaction(function () use ($id, $staffIds) {
            $ticket = $this->getById($id);
            $oldAssignedStaff = $ticket->assignedStaff->pluck('id')->toArray(); // Store old assigned staff

            $ticket->assignedStaff()->sync($staffIds);
            $ticket = $ticket->load(['user', 'branch', 'assignedStaff'])->loadCount('replies');

            // Send WhatsApp notification for staff assignment changes
            $newAssignedStaff = $ticket->assignedStaff->pluck('id')->toArray();
            if ($oldAssignedStaff !== $newAssignedStaff) {
                try {
                    $whatsappService = app(WhatsAppNotificationService::class);
                    $whatsappService->sendTicketAssignmentNotification($ticket, $oldAssignedStaff);
                } catch (\Exception $e) {
                    // Log error but don't fail the assignment
                    Log::error('Failed to send WhatsApp notification for staff assignment', [
                        'ticket_id' => $ticket->id,
                        'old_assigned_staff' => $oldAssignedStaff,
                        'new_assigned_staff' => $newAssignedStaff,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            return $ticket;
        });
    }

    private function generateTicketCode(?int $branchId): string
    {
        $branchCode = 'XXXX';
        if ($branchId) {
            $branch = \App\Models\Branch::find($branchId);
            if ($branch && $branch->code) {
                $branchCode = $branch->code;
            }
        }

        $month = $this->getRomanMonth(date('n'));
        $year = date('Y');

        do {
            $random = strtoupper(Str::random(3));
            $code = "T-NO.{$random}/SPK/{$branchCode}/{$month}/{$year}";
        } while (Ticket::where('code', $code)->exists());

        return $code;
    }

    private function getRomanMonth($month)
    {
        $map = [
            1 => 'I',
            2 => 'II',
            3 => 'III',
            4 => 'IV',
            5 => 'V',
            6 => 'VI',
            7 => 'VII',
            8 => 'VIII',
            9 => 'IX',
            10 => 'X',
            11 => 'XI',
            12 => 'XII'
        ];
        return $map[$month] ?? $month;
    }
}
