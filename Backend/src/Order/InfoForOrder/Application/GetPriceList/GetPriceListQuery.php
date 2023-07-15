<?php

namespace Tickets\Order\InfoForOrder\Application\GetPriceList;

use Shared\Domain\Bus\Query\Query;
use Shared\Domain\ValueObject\Uuid;

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
