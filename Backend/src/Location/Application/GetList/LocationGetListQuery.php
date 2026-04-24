<?php

declare(strict_types=1);

namespace Tickets\Location\Application\GetList;

use Shared\Domain\Bus\Query\Query;
use Shared\Domain\Criteria\Order;

class LocationGetListQuery implements Query
{
    public function __construct(
        private LocationGetListFilter $filter,
        private Order                 $orderBy,
    ) {
    }

    public function getFilter(): LocationGetListFilter
    {
        return $this->filter;
    }

    public function getOrderBy(): Order
    {
        return $this->orderBy;
    }
}
