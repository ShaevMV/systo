<?php

declare(strict_types=1);

namespace Shared\Services;

use App\Models\FriendlyTicket;
use App\Models\ListTicket;
use DB;
use Shared\Domain\ValueObject\Status;

class TicketService
{
    public function pushTicketFriendly(FriendlyTicket $ticket): bool
    {
        $rawModel = DB::connection('mysqlBaza')->table('friendly_tickets')
            ->where('kilter', '=', $ticket->id);
        if (!$rawModel->exists()) {
            return DB::connection('mysqlBaza')
                ->table('friendly_tickets')
                ->insert([
                    'kilter' => $ticket->id,
                    'project' => $ticket->fio,
                    'seller' => $ticket->seller,
                    'name' => $ticket->fio_friendly,
                    'date_order' => $ticket->created_at,
                    'email' => $ticket->email,
                    'comment' => $ticket->comment,
                    'festival_id' => $ticket->festival_id,
                ]);
        }

        $rawModel->update([
            'kilter' => $ticket->id,
            'project' => $ticket->fio,
            'seller' => $ticket->seller,
            'name' => $ticket->fio_friendly,
            'date_order' => $ticket->created_at,
            'email' => $ticket->email,
            'comment' => $ticket->comment,
            'festival_id' => $ticket->festival_id,
        ]);

        return true;
    }

    public function deleteTicketFriendly(int $id): void
    {
        $rawModel = DB::connection('mysqlBaza')->table('friendly_tickets')
            ->where('kilter', '=', $id);
        if (!$rawModel->exists()) {
            throw new \DomainException('Не найден билет f-' . $id);
        }

        $rawModel->update([
            'status' => Status::CANCEL
        ]);
    }


    public function pushTicketList(ListTicket $ticket): bool
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
                    'comment' => $ticket->comment,
                    'date_order' => $ticket->created_at,
                    'email' => $ticket->email,
                ]);
        }

        $rawModel->update([
            'kilter' => $ticket->id,
            'project' => $ticket->project,
            'curator' => $ticket->curator,
            'name' => $ticket->fio,
            'comment' => $ticket->comment,
            'date_order' => $ticket->created_at,
            'email' => $ticket->email,
        ]);

        return true;
    }

    public function deleteTicketList(int $id): void
    {
        $rawModel = DB::connection('mysqlBaza')->table('spisok_tickets')
            ->where('kilter', '=', $id);
        if (!$rawModel->exists()) {
            throw new \DomainException('Не найден билет s-' . $id);
        }

        $rawModel->update([
            'status' => Status::CANCEL
        ]);
    }
}
