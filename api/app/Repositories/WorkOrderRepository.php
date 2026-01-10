<?php

namespace App\Repositories;

use App\Interfaces\WorkOrderRepositoryInterface;
use App\Models\WorkOrder;
use App\Enums\WorkOrderStatus;
use App\Models\Ticket;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WorkOrderRepository implements WorkOrderRepositoryInterface
{
    public function getAll(
        ?string $search,
        ?int $limit,
        bool $execute
    ) {
        $user = Auth::user();
        /** @var \App\Models\User|null $user */

        $query = WorkOrder::with(['ticket', 'assignedUser'])
            ->where(function ($query) use ($search) {
                if ($search) {
                    $query->search($search);
                }
            });

        if ($user && $user->can('work-order-view-all')) {
            // admins and superadmins can see all work orders by permission
        } elseif ($user && ($user->hasRole('staff') || !$user->can('work-order-view-all'))) {
            $query->where('assigned_to', $user->id);
        } else {
            if ($user) {
                $query->whereHas('ticket', function ($ticketQuery) use ($user) {
                    $ticketQuery->where('user_id', $user->id);
                });
            } else {
                $query->whereRaw('1=0');
            }
        }

        $query->orderBy('created_at', 'desc');

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
        ?int $rowPerPage
    ) {
        $query = $this->getAll(
            $search,
            null,
            false
        );

        return $query->paginate($rowPerPage);
    }

    public function getById($id)
    {
        $user = Auth::user();
        /** @var \App\Models\User|null $user */

        $query = WorkOrder::with(['ticket', 'assignedUser'])
            ->where('id', $id);

        if ($user && $user->can('work-order-view-all')) {
            // admins and superadmins can access any work order by permission
        } elseif ($user && ($user->hasRole('staff') || !$user->can('work-order-view-all'))) {
            $query->where('assigned_to', $user->id);
        } else {
            if ($user) {
                $query->whereHas('ticket', function ($ticketQuery) use ($user) {
                    $ticketQuery->where('user_id', $user->id);
                });
            } else {
                $query->whereRaw('1=0');
            }
        }

        return $query->firstOrFail();
    }

    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            $workOrder = new WorkOrder();
            $workOrder->ticket_id = $data['ticket_id'];
            // Keep assigned_to for backward compatibility (first staff if array provided)
            $assignedStaff = $data['assigned_staff'] ?? [];
            $workOrder->assigned_to = !empty($assignedStaff) ? $assignedStaff[0] : ($data['assigned_to'] ?? null);
            $workOrder->number = $data['number'] ?? $this->generateWorkOrderNumber($workOrder->ticket_id);
            $workOrder->description = $data['description'] ?? null;
            $workOrder->status = $data['status'] ?? WorkOrderStatus::PENDING;

            // Document fields
            $workOrder->damage_unit = $data['damage_unit'] ?? null;
            $workOrder->contact_person = $data['contact_person'] ?? null;
            $workOrder->contact_phone = $data['contact_phone'] ?? null;
            $workOrder->product_type = $data['product_type'] ?? null;
            $workOrder->brand = $data['brand'] ?? null;
            $workOrder->model = $data['model'] ?? null;
            $workOrder->serial_number = $data['serial_number'] ?? null;
            $workOrder->purchase_date = $data['purchase_date'] ?? null;

            $workOrder->save();

            // Sync assigned staff (many-to-many)
            if (!empty($assignedStaff)) {
                $workOrder->assignedStaff()->sync($assignedStaff);
            }

            $workOrder = $workOrder->load(['ticket.branch', 'assignedUser', 'assignedStaff']);

            // Send WhatsApp notification to assigned technicians
            try {
                $whatsappService = app(\App\Services\WhatsAppNotificationService::class);
                $whatsappService->sendWorkOrderNotification($workOrder);
            } catch (\Exception $e) {
                // Log error but don't fail the work order creation
                \Illuminate\Support\Facades\Log::error('Failed to send WhatsApp notification for work order', [
                    'work_order_id' => $workOrder->id,
                    'error' => $e->getMessage()
                ]);
            }

            return $workOrder;
        });
    }

    public function update($id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $workOrder = $this->getById($id);
            $oldStatus = $workOrder->status;

            $user = Auth::user();
            /** @var \App\Models\User|null $user */

            if ($user && $user->hasRole('staff')) {
                // Staff can ONLY update status
                $workOrder->fill([
                    'status' => $data['status'] ?? $workOrder->status,
                ])->save();
            } else {
                // Handle assigned_staff array
                $assignedStaff = $data['assigned_staff'] ?? null;
                $assignedTo = $workOrder->assigned_to;
                if ($assignedStaff !== null && !empty($assignedStaff)) {
                    $assignedTo = $assignedStaff[0]; // First staff for backward compatibility
                } elseif (isset($data['assigned_to'])) {
                    $assignedTo = $data['assigned_to'];
                }

                $workOrder->fill([
                    'ticket_id' => $data['ticket_id'] ?? $workOrder->ticket_id,
                    'assigned_to' => $assignedTo,
                    'number' => $data['number'] ?? $workOrder->number,
                    'description' => $data['description'] ?? $workOrder->description,
                    'status' => $data['status'] ?? $workOrder->status,

                    // Document fields
                    'damage_unit' => $data['damage_unit'] ?? $workOrder->damage_unit,
                    'contact_person' => $data['contact_person'] ?? $workOrder->contact_person,
                    'contact_phone' => $data['contact_phone'] ?? $workOrder->contact_phone,
                    'product_type' => $data['product_type'] ?? $workOrder->product_type,
                    'brand' => $data['brand'] ?? $workOrder->brand,
                    'model' => $data['model'] ?? $workOrder->model,
                    'serial_number' => $data['serial_number'] ?? $workOrder->serial_number,
                    'purchase_date' => $data['purchase_date'] ?? $workOrder->purchase_date,
                ])->save();

                // Sync assigned staff if provided
                if ($assignedStaff !== null) {
                    $workOrder->assignedStaff()->sync($assignedStaff);
                }
            }

            // Send notification when Work Order is marked as "done"
            $newStatus = $workOrder->status;
            if ($oldStatus !== $newStatus && $newStatus->value === 'done') {
                try {
                    $whatsappService = app(\App\Services\WhatsAppNotificationService::class);
                    $whatsappService->sendWorkOrderDoneNotification($workOrder->load(['ticket', 'assignedUser', 'assignedStaff']));
                    Log::info('Work Order done notification sent', ['work_order_id' => $workOrder->id]);
                } catch (\Exception $e) {
                    Log::error('Failed to send Work Order done notification', [
                        'work_order_id' => $workOrder->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            return $workOrder->load(['ticket', 'assignedUser', 'assignedStaff']);
        });
    }

    public function delete($id)
    {
        return DB::transaction(function () use ($id) {
            $workOrder = $this->getById($id);
            return $workOrder->delete();
        });
    }

    public function getByTicketId($ticketId)
    {
        $user = Auth::user();
        /** @var \App\Models\User|null $user */

        $query = WorkOrder::with(['ticket', 'assignedUser'])
            ->where('ticket_id', $ticketId);

        if ($user && $user->can('work-order-view-all')) {
            // admins and superadmins can access any work order by permission
        } elseif ($user && ($user->hasRole('staff') || !$user->can('work-order-view-all'))) {
            $query->where('assigned_to', $user->id);
        } else {
            if ($user) {
                $query->whereHas('ticket', function ($ticketQuery) use ($user) {
                    $ticketQuery->where('user_id', $user->id);
                });
            } else {
                $query->whereRaw('1=0');
            }
        }

        return $query->first();
    }

    private function generateWorkOrderNumber(?int $ticketId): string
    {
        // Roman month mapping
        $romanMonths = [
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
        $month = $romanMonths[(int) date('n')];
        $year = date('Y');

        // Get branch code and unique code
        $branchCode = 'XXXX';
        $uniqueCode = null;

        if ($ticketId) {
            $ticket = Ticket::with('branch')->find($ticketId);

            // Get branch code from ticket's branch
            if ($ticket?->branch?->code) {
                $branchCode = $ticket->branch->code;
            }

            // Extract unique code from ticket code (e.g., "T-NO.ABC/SPK/JKT1/I/2026" -> "ABC")
            if ($ticket?->code) {
                // Pattern: T-NO.XXX/...
                if (preg_match('/T-NO\.([A-Z0-9]{3})\//', $ticket->code, $matches)) {
                    $uniqueCode = $matches[1];
                }
            }
        }

        // If no ticket or couldn't extract code, generate unique code
        if (!$uniqueCode) {
            do {
                $uniqueCode = strtoupper(Str::random(3));
                $testNumber = "SPK-NO.{$uniqueCode}/{$branchCode}/{$month}/{$year}";
            } while (WorkOrder::where('number', $testNumber)->exists());
        }

        return "SPK-NO.{$uniqueCode}/{$branchCode}/{$month}/{$year}";
    }
}
