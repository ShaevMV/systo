<?php

namespace App\Services;

use App\Models\FriendlyTicket;
use DB;

class TicketService
{

    public function pushTicket(FriendlyTicket $ticket): bool
    {
        $rawModel =
            DB::connection('mysqlBaza')->table('friendly_tickets')
                ->where('kilter', '=', $ticket->id);
            if (!$rawModel->exists())
            {


                return DB::connection('mysqlBaza')
                    ->table('friendly_tickets')
                    ->insert([
                        'kilter'=> $ticket->id,
                        'project' => $ticket->fio,
                        'seller' => $ticket->seller,
                        'name' => $ticket->fio_friendly,
                        'date_order' => $ticket->created_at,
                        'email' => $ticket->email,
                    ]);
            }

            return $rawModel->update([
                'kilter'=> $ticket->id,
                'project' => $ticket->fio,
                'seller' => $ticket->seller,
                'name' => $ticket->fio_friendly,
                'date_order' => $ticket->created_at,
                'email' => $ticket->email,
            ]) > 0;
    }
}
