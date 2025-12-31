<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Ticket;
use App\Enums\TicketStatus;
use App\Interfaces\TicketRepositoryInterface;

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

    private TicketRepositoryInterface $ticketRepository;

    public function __construct(TicketRepositoryInterface $ticketRepository)
    {
        parent::__construct();
        $this->ticketRepository = $ticketRepository;
    }

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
                $this->ticketRepository->update($ticket->id, [
                    'status' => TicketStatus::CLOSED->value,
                    'system_auto_close' => true
                ]);
                $this->info("Closed ticket {$ticket->code}");
            } catch (\Exception $e) {
                $this->error("Failed to close ticket {$ticket->code}: {$e->getMessage()}");
            }
        }

        $this->info('Auto-close tickets completed.');
    }
}
