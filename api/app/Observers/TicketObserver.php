<?php

namespace App\Observers;

use App\Models\Ticket;
use App\Enums\TicketStatus;
use App\Enums\WorkOrderStatus;
use Illuminate\Support\Facades\Log;

class TicketObserver
{
    /**
     * Handle the Ticket "updated" event.
     */
    public function updated(Ticket $ticket): void
    {
        // Check if status was changed
        if ($ticket->isDirty('status')) {
            $this->syncWorkOrderStatus($ticket);
        }
    }

    protected function syncWorkOrderStatus(Ticket $ticket)
    {
        // Load the relationship fresh to ensure we have latest status
        $workOrder = $ticket->workOrder()->first();

        if (!$workOrder) {
            return;
        }

        $newStatus = null;

        switch ($ticket->status) {
            case TicketStatus::OPEN:
                // Only revert to PENDING if not already done? 
                // Let's follow strict mapping for consistency as analyzed.
                if ($workOrder->status !== WorkOrderStatus::DONE) {
                    $newStatus = WorkOrderStatus::PENDING;
                }
                break;

            case TicketStatus::IN_PROGRESS:
                if ($workOrder->status !== WorkOrderStatus::DONE) {
                    $newStatus = WorkOrderStatus::IN_PROGRESS;
                }
                break;

            case TicketStatus::RESOLVED:
            case TicketStatus::CLOSED:
                // Always mark as DONE
                $newStatus = WorkOrderStatus::DONE;
                break;
        }

        if ($newStatus && $workOrder->status !== $newStatus) {
            Log::info("Syncing Ticket {$ticket->code} status ({$ticket->status->value}) to WorkOrder {$workOrder->ticket_number} -> {$newStatus->value}");

            $workOrder->status = $newStatus;

            $workOrder->saveQuietly(); // Use saveQuietly to avoid triggering WorkOrder events loop if any
        }
    }
}
