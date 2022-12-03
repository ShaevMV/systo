<?php

namespace Tickets\Ordering\OrderTicket\Application\GetOrderTicket;

use Tickets\Ordering\OrderTicket\Domain\OrderTicketItem;
use Tickets\Shared\Domain\Bus\Query\Response;

class ListResponse implements Response
{
    /**
     * @param  OrderTicketItem[]  $orderList
     */
    public function __construct(
        private array $orderList
    ) {
    }

    public function getOrderList(): array
    {
        return $this->orderList;
    }
}
