<?php

namespace Tickets\Order\InfoForOrder\Application\GetPriceList;

use Tickets\Shared\Domain\Bus\Query\Query;
use Tickets\Shared\Domain\ValueObject\Uuid;

class GetPriceListQuery implements Query
{
    public function __construct(
        private Uuid $festivalId
    )
    {
    }

    public function getFestivalId(): Uuid
    {
        return $this->festivalId;
    }
}
