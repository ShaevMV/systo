<?php

namespace Tickets\Order\InfoForOrder\Application\GetTicketType;

use Shared\Domain\Bus\Query\Query;
use Shared\Domain\ValueObject\Uuid;

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
