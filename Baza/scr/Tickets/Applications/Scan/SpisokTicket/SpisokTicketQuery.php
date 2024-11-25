<?php

declare(strict_types=1);

namespace Baza\Tickets\Applications\Scan\SpisokTicket;

use Baza\Shared\Domain\Bus\Query\Query;

class SpisokTicketQuery implements Query
{
    public function __construct(
        private int $kilter
    )
    {
    }

    public function getKilter(): int
    {
        return $this->kilter;
    }
}
