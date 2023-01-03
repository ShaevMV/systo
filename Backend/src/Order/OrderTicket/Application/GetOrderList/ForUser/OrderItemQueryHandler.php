<?php

namespace Tickets\Order\OrderTicket\Application\GetOrderList\ForUser;

use Tickets\Order\OrderTicket\Repositories\OrderTicketRepositoryInterface;
use Tickets\Order\OrderTicket\Responses\OrderTicketItemResponse;
use Tickets\Shared\Domain\Bus\Query\QueryHandler;

class OrderItemQueryHandler implements QueryHandler
{
    public function __construct(
        private OrderTicketRepositoryInterface $orderTicketRepository
    ) {
    }

    public function __invoke(OrderIdQuery $query): ?OrderTicketItemResponse
    {
        $orderTicketDto = $this->orderTicketRepository->findOrder($query->getOrderId());

        return is_null($orderTicketDto) ? null : OrderTicketItemResponse::fromOrderTicketDto($orderTicketDto);
    }
}
