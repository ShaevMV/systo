<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Application\GetOrderList;

use Tickets\Order\OrderTicket\Application\GetOrderList\ForAdmin\OrderFilterQuery;
use Tickets\Order\OrderTicket\Application\GetOrderList\ForAdmin\OrderListFilterQueryHandler;
use Tickets\Order\OrderTicket\Application\GetOrderList\ForUser\OrderIdQuery;
use Tickets\Order\OrderTicket\Application\GetOrderList\ForUser\OrderItemQueryHandler;
use Tickets\Order\OrderTicket\Application\GetOrderList\ForUser\OrderListQueryHandler;
use Tickets\Order\OrderTicket\Application\GetOrderList\ForUser\UserIdQuery;
use Tickets\Order\OrderTicket\Domain\OrderTicketItem;
use Tickets\Shared\Domain\Bus\Query\QueryBus;
use Tickets\Shared\Domain\ValueObject\Uuid;
use Tickets\Shared\Infrastructure\Bus\Query\InMemorySymfonyQueryBus;

class GetOrder
{
    private QueryBus $queryBus;

    public function __construct(
        OrderListQueryHandler $handler,
        OrderItemQueryHandler $itemQueryHandler,
        OrderListFilterQueryHandler $filterQueryHandler,
    ) {
        $this->queryBus = new InMemorySymfonyQueryBus([
            UserIdQuery::class => $handler,
            OrderIdQuery::class => $itemQueryHandler,
            OrderFilterQuery::class => $filterQueryHandler,
        ]);
    }

    /**
     * Вывести список заказов у пользователя
     *
     * @param  Uuid  $userId
     * @return ListResponse|null
     */
    public function listByUser(Uuid $userId): ?ListResponse
    {
        /** @var null|ListResponse $listItem */
        $listItem = $this->queryBus->ask(new UserIdQuery($userId));

        return $listItem;
    }

    /**
     * Вывести список заказов по фильтру
     *
     * @param  OrderFilterQuery  $filterQuery
     * @return ListResponse|null
     */
    public function listByFilter(OrderFilterQuery $filterQuery): ?ListResponse
    {
        /** @var null|ListResponse $listItem */
        $listItem = $this->queryBus->ask($filterQuery);

        return $listItem;
    }

    /**
     * Высети конкретный заказ
     *
     * @param  Uuid  $uuid
     * @return OrderTicketItem|null
     */
    public function getItemById(Uuid $uuid): ?OrderTicketItem
    {
        /** @var OrderTicketItem|null $result */
        $result = $this->queryBus->ask(new OrderIdQuery($uuid));

        return $result;
    }
}
