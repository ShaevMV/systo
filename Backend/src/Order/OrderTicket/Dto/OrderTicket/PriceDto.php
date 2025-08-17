<?php

namespace Tickets\Order\OrderTicket\Dto\OrderTicket;

use Shared\Domain\Entity\AbstractionEntity;

class PriceDto extends AbstractionEntity
{
    protected float $totalPrice;
    private int|float $price;


    public function __construct(
        protected int $priceItem,
        protected int $count,
        protected float $discount = 0.0,

    ) {

        $this->price = $this->priceItem * $this->count;
        $this->totalPrice = $this->price - $this->discount;
        if($this->discount>0) {
            $this->priceItem = (int)($this->totalPrice / $this->count);
        }
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
            $data['count'],
            $data['discount']
        );
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function getPriceItem(): int
    {
        return $this->priceItem;
    }

    /**
     * @return int
     */
    public function getCount(): int
    {
        return $this->count;
    }
}
