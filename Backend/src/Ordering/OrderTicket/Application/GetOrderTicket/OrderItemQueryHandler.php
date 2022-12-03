<?php

namespace Tickets\Ordering\OrderTicket\Application\GetOrderTicket;

use Tickets\Ordering\OrderTicket\Domain\OrderTicketItem;
use Tickets\Ordering\OrderTicket\Repositories\OrderTicketRepositoryInterface;
use Tickets\Shared\Domain\Bus\Query\QueryHandler;

class OrderItemQueryHandler implements QueryHandler
{
    public function __construct(
        private OrderTicketRepositoryInterface $orderTicketRepository
    ){
    }

    public function __invoke(OrderIdQuery $query): OrderTicketItem
    {
        return $this->orderTicketRepository->findOrder($query->getOrderId());
    }
}
