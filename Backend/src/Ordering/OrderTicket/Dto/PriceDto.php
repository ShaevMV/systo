<?php

namespace Tickets\Ordering\OrderTicket\Dto;

class PriceDto
{
    public function __construct(
        private float $totalPrice,
        private float $discount
    ){
    }

    public function getTotalPrice(): float
    {
        return $this->totalPrice;
    }

    public function getDiscount(): float
    {
        return $this->discount;
    }
}
