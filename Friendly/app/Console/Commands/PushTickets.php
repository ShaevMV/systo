<?php

namespace App\Console\Commands;

use App\Models\FriendlyTicket;
use Shared\Services\TicketService;
use Illuminate\Console\Command;

class PushTickets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tickets:push';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(
        TicketService $ticketService
    )
    {
        $tickets = FriendlyTicket::all();

        foreach ($tickets as $ticket) {
            $r = $ticketService->pushTicketFriendly($ticket);
            $this->info('push '. $ticket->id);
        }

        return 0;
    }
}
