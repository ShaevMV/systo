<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Application\GetOrderList\ForUser;

use Tickets\Order\OrderTicket\Responses\OrderTicketItemResponse;
use Tickets\Order\Shared\Repositories\OrderTicketRepositoryInterface;
use Tickets\Shared\Domain\Bus\Query\QueryHandler;

class OrderItemQueryHandler implements QueryHandler
{
    public function __construct(
        private OrderTicketRepositoryInterface $orderTicketRepository
    ){
    }

    public function __invoke(OrderIdQuery $query): ?OrderTicketItemResponse
    {
        return $this->orderTicketRepository->getItem($query->getOrderId());
    }
}
