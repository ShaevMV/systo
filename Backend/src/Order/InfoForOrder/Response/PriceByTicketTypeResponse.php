<?php

namespace Tickets\Order\InfoForOrder\Response;

use Shared\Domain\Bus\Query\Response;

class PriceByTicketTypeResponse implements Response
{
    public function __construct(
        private float $price,
        private bool $isGroupType = false
    ){
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    /**
     * @return bool
     */
    public function isGroupType(): bool
    {
        return $this->isGroupType;
    }

}
