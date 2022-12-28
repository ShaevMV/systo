<?php

namespace Tickets\Ordering\InfoForOrder\Application\GetPriceByTicketType;

use Tickets\Shared\Domain\Bus\Query\Query;
use Tickets\Shared\Domain\ValueObject\Uuid;

class GetPriceByTicketTypeQuery implements Query
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
