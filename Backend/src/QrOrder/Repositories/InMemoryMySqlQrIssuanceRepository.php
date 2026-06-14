<?php

declare(strict_types=1);

namespace Tickets\QrOrder\Repositories;

use App\Models\Festival\TicketTypeFestivalModel;
use App\Models\Tickets\TicketModel;
use Shared\Domain\ValueObject\Uuid;

final class InMemoryMySqlQrIssuanceRepository implements QrIssuanceRepositoryInterface
{
    public function getKilter(Uuid $ticketId): ?int
    {
        $kilter = TicketModel::whereId($ticketId->value())->value('kilter');

        return $kilter === null ? null : (int) $kilter;
    }

    public function findTemplate(Uuid $festivalId, Uuid $ticketTypeId): ?array
    {
        $row = TicketTypeFestivalModel::query()
            ->where('festival_id', $festivalId->value())
            ->where('ticket_type_id', $ticketTypeId->value())
            ->first();

        if ($row === null) {
            return null;
        }

        return ['pdf' => $row->pdf, 'email' => $row->email];
    }
}
