<?php

declare(strict_types = 1);

namespace Tickets\Festival\Application\GetInfoForOrder;

use Shared\Domain\Bus\Query\Query;
use Shared\Domain\ValueObject\Uuid;

final class ListTicketTypeQuery implements Query
{
    public function __construct(
        private Uuid $festivalId,
        private bool $isAllPrice = false,
        private bool $isPusher = false,
    )
    {
    }

    public function getFestivalId(): Uuid
    {
        return $this->festivalId;
    }

    public function isAllPrice(): bool
    {
        return $this->isAllPrice;
    }

    public function isPusher(): bool
    {
        return $this->isPusher;
    }
}
