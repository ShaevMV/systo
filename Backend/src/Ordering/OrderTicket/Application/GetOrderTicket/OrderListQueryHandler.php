<?php

namespace Tickets\Ordering\OrderTicket\Application\GetOrderTicket;

use Tickets\Ordering\OrderTicket\Repositories\OrderTicketRepositoryInterface;
use Tickets\Shared\Domain\Bus\Query\QueryHandler;

class OrderListQueryHandler implements QueryHandler
{
    public function __construct(
        private OrderTicketRepositoryInterface $orderTicket
    ){
    }

    public function __invoke(UserIdQuery $query): ListResponse
    {
        $orderTicketItem = $this->orderTicket->getUserList($query->getUserId());

        return new ListResponse($orderTicketItem);
    }
}
