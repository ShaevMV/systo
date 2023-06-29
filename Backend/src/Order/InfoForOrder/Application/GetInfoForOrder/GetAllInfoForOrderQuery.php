<?php

declare(strict_types = 1);

namespace Tickets\Order\InfoForOrder\Application\GetInfoForOrder;

use Tickets\Shared\Domain\Bus\Query\Query;
use Tickets\Shared\Domain\ValueObject\Uuid;

final class GetAllInfoForOrderQuery implements Query
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
