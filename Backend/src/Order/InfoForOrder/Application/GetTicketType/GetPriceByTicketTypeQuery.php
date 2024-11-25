<?php

namespace Tickets\Order\InfoForOrder\Application\GetTicketType;

use Carbon\Carbon;
use Shared\Domain\Bus\Query\Query;
use Shared\Domain\ValueObject\Uuid;

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
