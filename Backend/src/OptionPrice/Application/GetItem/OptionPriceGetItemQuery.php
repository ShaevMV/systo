<?php

declare(strict_types=1);

namespace Tickets\OptionPrice\Application\GetItem;

use Shared\Domain\Bus\Query\Query;
use Shared\Domain\ValueObject\Uuid;

class OptionPriceGetItemQuery implements Query
{
    public function __construct(
        private Uuid $id,
    ) {
    }

    public function getId(): Uuid
    {
        return $this->id;
    }
}
