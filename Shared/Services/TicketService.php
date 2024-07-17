<?php

declare(strict_types=1);

namespace Shared\Services;

use App\Models\Auto;
use App\Models\FriendlyTicket;
use App\Models\ListTicket;
use DB;
use DomainException;
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
            throw new DomainException('Не найден билет f-' . $id);
        }

        $rawModel->update([
            'status' => Status::CANCEL
        ]);
    }

    public function deleteAuto(int $id): void
    {
        $rawModel = DB::connection('mysqlBaza')->table('auto')
            ->where('id', '=', $id);
        if (!$rawModel->exists()) {
            throw new DomainException('Автомобиль с id не найден ' . $id);
        }

        $rawModel->delete();
    }

    public function pushTicketList(ListTicket $ticket): bool
    {
        $rawModel =
            DB::connection('mysqlBaza')->table('spisok_tickets')
                ->where('kilter', '=', $ticket->id);
        $data = [
            'kilter' => $ticket->id,
            'project' => $ticket->project,
            'curator' => $ticket->curator,
            'name' => $ticket->fio,
            'comment' => $ticket->comment,
            'festival_id' => $ticket->festival_id,
            'date_order' => $ticket->created_at,
            'email' => $ticket->email,
        ];
        if (!$rawModel->exists()) {
            return DB::connection('mysqlBaza')
                ->table('spisok_tickets')
                ->insert($data);
        }

        $rawModel->update($data);

        return true;
    }

    public function pushAutoList(Auto $ticket): bool
    {
        return DB::connection('mysqlBaza')
            ->table('auto')
            ->insert([
                'project' => $ticket->project,
                'curator' => $ticket->curator,
                'festival_id' => $ticket->festival_id,
                'auto' => $ticket->auto,
                'comment' => $ticket->comment,
            ]);
    }

    public function deleteTicketList(int $id): void
    {
        $rawModel = DB::connection('mysqlBaza')->table('spisok_tickets')
            ->where('kilter', '=', $id);
        if (!$rawModel->exists()) {
            throw new DomainException('Не найден билет s-' . $id);
        }

        $rawModel->update([
            'status' => Status::CANCEL
        ]);
    }

    public static function getZeroForKilter(int $kilter): string
    {
        if($kilter < 10) {
            return '000'.$kilter;
        }

        if($kilter < 100) {
            return '00'.$kilter;
        }
        if($kilter < 1000) {
            return '0'.$kilter;
        }

        return (string)$kilter;
    }
}
