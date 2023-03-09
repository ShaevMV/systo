<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Application\GetOrderList\ForUser;

use Tickets\Order\OrderTicket\Responses\ListResponse;
use Tickets\Order\Shared\Repositories\OrderTicketRepositoryInterface;
use Tickets\Shared\Domain\Bus\Query\QueryHandler;

class OrderListQueryHandler implements QueryHandler
{
    public function __construct(
        private OrderTicketRepositoryInterface $orderTicket
    ) {
    }

    public function __invoke(UserIdQuery $query): ?ListResponse
    {
        $orderTicketItemForList = $this->orderTicket->getUserList($query->getUserId());


        return count($orderTicketItemForList) > 0 ? new ListResponse($orderTicketItemForList) : null;
    }
}
