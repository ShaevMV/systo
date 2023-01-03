<?php

namespace Tickets\Order\OrderTicket\Dto\OrderTicket;

use Tickets\Shared\Domain\Entity\AbstractionEntity;

class PriceDto extends AbstractionEntity
{
    protected float $totalPrice;

    public function __construct(
        protected float $price,
        protected float $discount = 0.0
    ) {
        $this->totalPrice = $this->price - $this->discount;
    }

    public function getTotalPrice(): float
    {
        return $this->totalPrice;
    }

    public function getDiscount(): float
    {
        return $this->discount;
    }

    public static function fromState(array $data): self
    {
        return new self(
            $data['price'],
            $data['discount']
        );
    }

    public function getPrice(): float
    {
        return $this->price;
    }
}
