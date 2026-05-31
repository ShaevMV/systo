<?php

declare(strict_types=1);

namespace Tickets\OptionPrice\Application\GetList;

use Shared\Domain\Bus\Query\Query;
use Shared\Domain\Criteria\Order;

class OptionPriceGetListQuery implements Query
{
    public function __construct(
        private array $filter,
        private Order $orderBy,
    ) {
    }

    public function getFilter(): array
    {
        return $this->filter;
    }

    public function getOrderBy(): Order
    {
        return $this->orderBy;
    }
}
