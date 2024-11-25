<?php

namespace App\Console\Commands;

use App\Models\Ordering\OrderTicketModel;
use App\Models\Tickets\TicketModel;
use Illuminate\Console\Command;

class InsertFestivalId extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ticket:insertFestivalId';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Прописать фистевал id у билетов';

    public function handle()
    {
        /** @var TicketModel[] $tickets */
        $tickets = TicketModel::with([
            'orderTicket',
        ])->get();
        foreach ($tickets as $ticket) {
            /** @var OrderTicketModel $o */
            $o = $ticket->orderTicket()->first();
            $ticket->festival_id = $o->festival_id;
            $ticket->save();
        }

        return Command::SUCCESS;
    }
}
