<?php

namespace Tickets\Ordering\InfoForOrder\Application\GetPriceByTicketType;

use Tickets\Shared\Domain\Bus\Query\Response;

class PriceByTicketTypeResponse implements Response
{
    public function __construct(
        private float $price
    ){
    }

    public function getPrice(): float
    {
        return $this->price;
    }

}
