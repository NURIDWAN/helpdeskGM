<?php

namespace App\Repositories;

use App\Interfaces\WorkOrderRepositoryInterface;
use App\Models\WorkOrder;
use App\Enums\WorkOrderStatus;
use App\Models\Ticket;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

        if ($user && ($user->hasRole('admin') || $user->hasRole('superadmin'))) {
            // admins and superadmins can see all work orders
        } elseif ($user && $user->hasRole('staff')) {
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

        if ($user && ($user->hasRole('admin') || $user->hasRole('superadmin'))) {
            // admins and superadmins can access any work order
        } elseif ($user && $user->hasRole('staff')) {
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
            $workOrder->assigned_to = $data['assigned_to'];
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

            return $workOrder->load(['ticket', 'assignedUser']);
        });
    }

    public function update($id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $workOrder = $this->getById($id);

            $user = Auth::user();
            /** @var \App\Models\User|null $user */

            if ($user && $user->hasRole('staff')) {
                // Staff can ONLY update status
                $workOrder->fill([
                    'status' => $data['status'] ?? $workOrder->status,
                ])->save();
            } else {
                $workOrder->fill([
                    'ticket_id' => $data['ticket_id'] ?? $workOrder->ticket_id,
                    'assigned_to' => $data['assigned_to'] ?? $workOrder->assigned_to,
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
            }

            return $workOrder->load(['ticket', 'assignedUser']);
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

        if ($user && ($user->hasRole('admin') || $user->hasRole('superadmin'))) {
            // admins and superadmins can access any work order
        } elseif ($user && $user->hasRole('staff')) {
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
        if ($ticketId) {
            $ticket = Ticket::with('branch')->find($ticketId);
            $branchName = $ticket?->branch?->name ?? 'General';
        } else {
            // For standalone work orders, use 'General' branch
            $branchName = 'General';
        }

        $lettersOnly = strtoupper(preg_replace('/[^A-Za-z]/', '', $branchName));
        $consonants = preg_replace('/[AEIOU]/', '', $lettersOnly);
        $branchCode = substr($consonants ?: $lettersOnly, 0, 3) ?: 'GEN';

        $month = now()->format('m');
        $year = now()->format('Y');

        $countThisPeriod = WorkOrder::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->count() + 1;
        $sequence = str_pad((string) $countThisPeriod, 3, '0', STR_PAD_LEFT);

        return $sequence . '/SPK-' . $branchCode . '/' . $month . '/' . $year;
    }
}
