<?php

namespace Tickets\Order\OrderTicket\Application\GetOrderList\ForUser;

use Tickets\Order\OrderTicket\Repositories\OrderTicketRepositoryInterface;
use Tickets\Order\OrderTicket\Responses\OrderTicketItem;
use Tickets\Shared\Domain\Bus\Query\QueryHandler;

class OrderItemQueryHandler implements QueryHandler
{
    public function __construct(
        private OrderTicketRepositoryInterface $orderTicketRepository
    ) {
    }

    public function __invoke(OrderIdQuery $query): ?OrderTicketItem
    {
        $orderTicketDto = $this->orderTicketRepository->findOrder($query->getOrderId());

        return is_null($orderTicketDto) ? null : OrderTicketItem::fromOrderTicketDto($orderTicketDto);
    }
}
