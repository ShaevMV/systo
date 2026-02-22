<?php

declare(strict_types=1);

namespace Tickets\TicketType\Application\GetList;

use Shared\Domain\Bus\Query\Query;
use Shared\Domain\Criteria\Order;

class TicketTypeGetListQuery implements Query
{
    public function __construct(
        private TicketTypeGetListFilter $filter,
        private Order $orderBy,
    )
    {
    }

    public function getFilter(): TicketTypeGetListFilter
    {
        return $this->filter;
    }

    public function getOrderBy(): Order
    {
        return $this->orderBy;
    }
}
