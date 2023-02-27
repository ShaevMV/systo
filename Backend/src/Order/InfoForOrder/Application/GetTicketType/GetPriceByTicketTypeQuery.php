<?php

namespace Tickets\Order\InfoForOrder\Application\GetTicketType;

use Carbon\Carbon;
use Tickets\Shared\Domain\Bus\Query\Query;
use Tickets\Shared\Domain\ValueObject\Uuid;

class GetPriceByTicketTypeQuery implements Query
{
    public function __construct(
        private Uuid $uuid,
        private ?Carbon $carbon = null,
    ){
    }

    public function getUuid(): Uuid
    {
        return $this->uuid;
    }

    public function getCarbon(): ?Carbon
    {
        return $this->carbon;
    }
}
