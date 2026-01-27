<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Ticket;
use App\Enums\TicketStatus;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;

class AutoCloseTickets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tickets:auto-close';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Auto close resolved tickets after 24 hours of inactivity';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting auto-close tickets...');

        // Find tickets resolved > 24 hours ago
        $tickets = Ticket::where('status', TicketStatus::RESOLVED)
            ->where('updated_at', '<', now()->subHours(24))
            ->get();

        $this->info("Found " . $tickets->count() . " tickets to close.");

        foreach ($tickets as $ticket) {
            try {
                $oldStatus = $ticket->status;
                
                // Update ticket directly
                $ticket->status = TicketStatus::CLOSED;
                if (!$ticket->completed_at) {
                    $ticket->completed_at = now();
                }
                $ticket->save();

                $this->info("Closed ticket {$ticket->code}");

                // Send notification
                try {
                    $notificationService = app(NotificationService::class);
                    $notificationService->sendTicketStatusUpdateNotification($ticket, $oldStatus->value);
                } catch (\Exception $e) {
                    Log::error('Auto-close notification failed', [
                        'ticket_id' => $ticket->id,
                        'error' => $e->getMessage()
                    ]);
                }
            } catch (\Exception $e) {
                $this->error("Failed to close ticket {$ticket->code}: {$e->getMessage()}");
            }
        }

        $this->info('Auto-close tickets completed.');
    }
}

