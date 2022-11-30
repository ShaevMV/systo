<?php

declare(strict_types=1);

namespace Tickets\Ordering\OrderTicket\Application\GetOrderTicketsList;

use Tickets\Ordering\OrderTicket\Domain\OrderTicketList;
use Tickets\Shared\Domain\Bus\Query\QueryBus;
use Tickets\Shared\Domain\ValueObject\Uuid;
use Tickets\Shared\Infrastructure\Bus\Query\InMemorySymfonyQueryBus;

class ToGetList
{
    private QueryBus $queryBus;

    public function __construct(OrdersQueryHandler $handler)
    {
        $this->queryBus = new InMemorySymfonyQueryBus([
           OrdersQuery::class => $handler
        ]);
    }

    public function byUser(Uuid $userId): OrderTicketList
    {
        /** @var ListResponse $listItem */
        $listItem = $this->queryBus->ask(new OrdersQuery($userId));

        return new OrderTicketList($listItem->getOrderList());
    }
}
