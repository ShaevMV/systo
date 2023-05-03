<?php

namespace Baza\Tickets\Applications\Scan\ElTicket;

use Baza\Shared\Domain\Bus\Query\Query;
use Baza\Shared\Domain\ValueObject\Uuid;

class ElTicketQuery implements Query
{
    public function __construct(
        private Uuid $uuid
    )
    {
    }

    public function getUuid(): Uuid
    {
        return $this->uuid;
    }
}
