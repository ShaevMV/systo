<?php

namespace Tickets\Ordering\OrderTicket\Application\GetOrderTicketsList;

use Tickets\Ordering\OrderTicket\Repositories\OrderTicketInterface;
use Tickets\Shared\Domain\Bus\Query\QueryHandler;

class OrdersQueryHandler implements QueryHandler
{
    public function __construct(
        private OrderTicketInterface $orderTicket
    ){
    }

    public function __invoke(OrdersQuery $query): ListResponse
    {
        $orderTicketItem = $this->orderTicket->getUserList($query->getUserId());

        return new ListResponse($orderTicketItem);
    }
}
