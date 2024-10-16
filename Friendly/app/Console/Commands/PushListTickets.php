<?php

namespace App\Console\Commands;

use App\Models\ListTicket;
use Shared\Services\TicketService;
use Illuminate\Console\Command;

class PushListTickets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tickets:list:push';

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
        $tickets = ListTicket::where('festival_id','=','9d679bcf-b438-4ddb-ac04-023fa9bff4b5')->get();

        foreach ($tickets as $ticket) {
            $ticketService->pushTicketList($ticket,'9d679bcf-b438-4ddb-ac04-023fa9bff4b5');
            $this->info('push '. $ticket->id);
        }

        return 0;
    }
}