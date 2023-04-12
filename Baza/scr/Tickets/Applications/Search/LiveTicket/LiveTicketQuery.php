<?php

declare(strict_types=1);

namespace Baza\Tickets\Applications\Search\LiveTicket;

use Baza\Shared\Domain\Bus\Query\Query;

class LiveTicketQuery implements Query
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
