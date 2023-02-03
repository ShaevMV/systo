<?php

namespace Tickets\Order\InfoForOrder\Application\GetTicketType;

use Tickets\Shared\Domain\Bus\Query\Query;
use Tickets\Shared\Domain\ValueObject\Uuid;

class GetTicketTypeQuery implements Query
{
    public function __construct(
        private Uuid $uuid
    ){
    }

    public function getUuid(): Uuid
    {
        return $this->uuid;
    }
}
