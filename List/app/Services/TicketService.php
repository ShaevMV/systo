<?php

namespace App\Services;


use App\Models\ListTicket;
use Carbon\Carbon;
use DB;

class TicketService
{

    public function pushTicket(ListTicket $ticket): bool
    {
        $rawModel =
            DB::connection('mysqlBaza')->table('spisok_tickets')
                ->where('kilter', '=', $ticket->id);
        if (!$rawModel->exists()) {
            return DB::connection('mysqlBaza')
                ->table('spisok_tickets')
                ->insert([
                    'kilter' => $ticket->id,
                    'project' => $ticket->project,
                    'curator' => $ticket->curator,
                    'name' => $ticket->fio,
                    'date_order' => $ticket->created_at,
                    'email' => $ticket->email,
                ]);
        }

        return $rawModel->update([
                'kilter' => $ticket->id,
                'project' => $ticket->project,
                'curator' => $ticket->curator,
                'name' => $ticket->fio,
                'date_order' => $ticket->created_at,
                'email' => $ticket->email,
            ]) > 0;
    }

    public function delTicket(int $id): bool
    {
        $rawModel =
            DB::connection('mysqlBaza')->table('spisok_tickets')
                ->where('kilter', '=', $id);


        if($rawModel->exists()) {
            $rawModel->update([
                'deleted_at' => Carbon::now()
            ]);
        }
    }
}