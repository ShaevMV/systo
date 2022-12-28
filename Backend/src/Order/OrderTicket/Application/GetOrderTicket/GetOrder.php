<?php

declare(strict_types=1);

namespace Tickets\Ordering\OrderTicket\Application\GetOrderTicket;

use Tickets\Ordering\OrderTicket\Application\GetOrderTicket\ForAdmin\OrderFilterQuery;
use Tickets\Ordering\OrderTicket\Application\GetOrderTicket\ForAdmin\OrderListFilterQueryHandler;
use Tickets\Ordering\OrderTicket\Application\GetOrderTicket\ForUser\OrderIdQuery;
use Tickets\Ordering\OrderTicket\Application\GetOrderTicket\ForUser\OrderItemQueryHandler;
use Tickets\Ordering\OrderTicket\Application\GetOrderTicket\ForUser\OrderListQueryHandler;
use Tickets\Ordering\OrderTicket\Application\GetOrderTicket\ForUser\UserIdQuery;
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
