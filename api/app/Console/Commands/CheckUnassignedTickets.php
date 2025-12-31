<?php

namespace App\Console\Commands;

use App\Models\Ticket;
use App\Services\WhatsAppNotificationService;
use Illuminate\Console\Command;

class CheckUnassignedTickets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tickets:check-unassigned';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for tickets unassigned for more than 1 hour and send alerts';

    /**
     * Execute the console command.
     */
    public function handle(WhatsAppNotificationService $whatsAppService)
    {
        $this->info('Checking for unassigned tickets...');

        $tickets = Ticket::where('status', 'open')
            ->doesntHave('assignedStaff')
            ->where('created_at', '<=', now()->subHour())
            ->whereNull('unassigned_alert_sent_at')
            ->get();

        if ($tickets->isEmpty()) {
            $this->info('No unassigned tickets found requiring alert.');
            return;
        }

        $this->info("Found {$tickets->count()} tickets.");

        foreach ($tickets as $ticket) {
            $this->info("Sending alert for ticket {$ticket->code}...");

            $whatsAppService->sendUnassignedTicketAlert($ticket);

            $ticket->update(['unassigned_alert_sent_at' => now()]);
        }

        $this->info('Done.');
    }
}
