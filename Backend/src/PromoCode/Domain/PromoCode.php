<?php

declare(strict_types=1);

namespace Tickets\PromoCode\Domain;


use Shared\Domain\Aggregate\AggregateRoot;
use Shared\Domain\ValueObject\Uuid;

class PromoCode extends AggregateRoot
{
    public function __construct(
        private Uuid $id,
        private string $name,
        private float $discount,
        private bool $is_percent,
        private ?int $limit = null,
    )
    {
    }
}
