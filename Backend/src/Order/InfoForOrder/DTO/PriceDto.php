<?php

declare(strict_types=1);

namespace Tickets\Order\InfoForOrder\DTO;

use Carbon\Carbon;
use Shared\Domain\Entity\AbstractionEntity;
use Shared\Domain\ValueObject\Uuid;

class PriceDto extends AbstractionEntity
{
    public function __construct(
        protected Uuid $uuid,
        protected float $price,
        protected Carbon $beforeDate
    )
    {
    }

    public static function fromState(array $data): self
    {
        return new self(
            new Uuid($data['id']),
            (float) $data['price'],
            new Carbon($data['before_date'])
        );
    }

    public function getPrice(): float
    {
        return $this->price;
    }
}
