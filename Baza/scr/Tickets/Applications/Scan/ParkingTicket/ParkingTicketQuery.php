<?php

declare(strict_types=1);

namespace Baza\Tickets\Applications\Scan\ParkingTicket;

use Baza\Shared\Domain\Bus\Query\Query;

class ParkingTicketQuery implements Query
{
    public function __construct(
        private int $kilter,
        private string $type,
    )
    {
    }

    public function getKilter(): int
    {
        return $this->kilter;
    }

    public function getType(): string
    {
        return $this->type;
    }
}
