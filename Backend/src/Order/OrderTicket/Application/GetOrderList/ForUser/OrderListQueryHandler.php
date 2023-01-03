<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Application\GetOrderList\ForUser;

use Tickets\Order\OrderTicket\Application\GetOrderList\ListResponse;
use Tickets\Order\OrderTicket\Repositories\OrderTicketRepositoryInterface;
use Tickets\Shared\Domain\Bus\Query\QueryHandler;

class OrderListQueryHandler implements QueryHandler
{
    public function __construct(
        private OrderTicketRepositoryInterface $orderTicket
    ) {
    }

    public function __invoke(UserIdQuery $query): ?ListResponse
    {
        $orderTicketItem = $this->orderTicket->getUserList($query->getUserId());

        return count($orderTicketItem) > 0 ? new ListResponse($orderTicketItem) : null;
    }
}
