<?php

declare(strict_types=1);

namespace Tickets\Ordering\OrderTicket\Application\GetOrderTicket;

use Tickets\Ordering\OrderTicket\Domain\OrderTicketItem;
use Tickets\Ordering\OrderTicket\Domain\OrderTicketList;
use Tickets\Shared\Domain\Bus\Query\QueryBus;
use Tickets\Shared\Domain\ValueObject\Uuid;
use Tickets\Shared\Infrastructure\Bus\Query\InMemorySymfonyQueryBus;

class GetOrder
{
    private QueryBus $queryBus;

    public function __construct(
        OrderListQueryHandler $handler,
        OrderItemQueryHandler $itemQueryHandler,
    ) {
        $this->queryBus = new InMemorySymfonyQueryBus([
            UserIdQuery::class => $handler,
            OrderIdQuery::class => $itemQueryHandler,
        ]);
    }

    /**
     * Вывести список заказов у пользователя
     *
     * @param  Uuid  $userId
     * @return OrderTicketList
     */
    public function listByUser(Uuid $userId): OrderTicketList
    {
        /** @var ListResponse $listItem */
        $listItem = $this->queryBus->ask(new UserIdQuery($userId));

        return new OrderTicketList($listItem->getOrderList());
    }

    /**
     * Высети конкретный заказ
     *
     * @param  Uuid  $uuid
     * @return OrderTicketItem
     */
    public function getItemById(Uuid $uuid): OrderTicketItem
    {
        /** @var OrderTicketItem $result */
        $result = $this->queryBus->ask(new OrderIdQuery($uuid));

        return $result;
    }
}
