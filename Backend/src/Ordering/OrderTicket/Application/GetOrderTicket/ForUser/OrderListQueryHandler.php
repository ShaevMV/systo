<?php

namespace Tickets\Ordering\OrderTicket\Application\GetOrderTicket\ForUser;

use Tickets\Ordering\OrderTicket\Application\GetOrderTicket\ListResponse;
use Tickets\Ordering\OrderTicket\Repositories\OrderTicketRepositoryInterface;
use Tickets\Shared\Domain\Bus\Query\QueryHandler;

class OrderListQueryHandler implements QueryHandler
{
    public function __construct(
        private OrderTicketRepositoryInterface $orderTicket
    ){
    }

    public function __invoke(UserIdQuery $query): ?ListResponse
    {
        $orderTicketItem = $this->orderTicket->getUserList($query->getUserId());

        return count($orderTicketItem)>0 ? new ListResponse($orderTicketItem) : null;
    }
}
