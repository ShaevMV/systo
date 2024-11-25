<?php

namespace Tickets\Order\InfoForOrder\Application\GetInfoForOrder;

use Shared\Domain\Bus\Query\Query;

class TypesOfPaymentQuery implements Query
{
    public function __construct(
        private bool $isForAdmin = false
    )
    {
    }

    public function isForAdmin(): bool
    {
        return $this->isForAdmin;
    }
}
