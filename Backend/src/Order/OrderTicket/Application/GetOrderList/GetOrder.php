<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Application\GetOrderList;

use Tickets\Order\OrderTicket\Application\GetOrderList\ForAdmin\OrderFilterQuery;
use Tickets\Order\OrderTicket\Application\GetOrderList\ForFriendly\OrderFilterQuery as OrderFilterForFriendlyQuery;
use Tickets\Order\OrderTicket\Application\GetOrderList\ForAdmin\OrderListFilterQueryHandler;
use Tickets\Order\OrderTicket\Application\GetOrderList\ForFriendly\OrderListFilterQueryHandler as OrderFilterForFriendlyQueryHandler;
use Tickets\Order\OrderTicket\Application\GetOrderList\ForUser\OrderIdQuery;
use Tickets\Order\OrderTicket\Application\GetOrderList\ForUser\OrderItemQueryHandler;
use Tickets\Order\OrderTicket\Application\GetOrderList\ForUser\OrderListQueryHandler;
use Tickets\Order\OrderTicket\Application\GetOrderList\ForUser\UserIdQuery;
use Tickets\Order\OrderTicket\Responses\ListResponse;
use Tickets\Order\OrderTicket\Responses\OrderTicketItemResponse;
use Shared\Domain\Bus\Query\QueryBus;
use Shared\Domain\ValueObject\Uuid;
use Shared\Infrastructure\Bus\Query\InMemorySymfonyQueryBus;

class GetOrder
{
    private QueryBus $queryBus;

    public function __construct(
        OrderListQueryHandler $handler,
        OrderItemQueryHandler $itemQueryHandler,
        OrderListFilterQueryHandler $filterQueryHandler,
        OrderFilterForFriendlyQueryHandler $orderListFilterQueryHandler,
    ) {
        $this->queryBus = new InMemorySymfonyQueryBus([
            UserIdQuery::class => $handler,
            OrderIdQuery::class => $itemQueryHandler,
            OrderFilterQuery::class => $filterQueryHandler,
            OrderFilterForFriendlyQuery::class => $orderListFilterQueryHandler,
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
     * Вывести список заказов по фильтру
     *
     * @param  OrderFilterForFriendlyQuery  $filterQuery
     * @return ListResponse|null
     */
    public function listByFilterForFriendly(OrderFilterForFriendlyQuery $filterQuery): ?ListResponse
    {
                /** @var null|ListResponse $listItem */
        $listItem = $this->queryBus->ask($filterQuery);

        return $listItem;
    }

    /**
     * Высети конкретный заказ
     *
     * @param  Uuid  $uuid
     * @return OrderTicketItemResponse|null
     */
    public function getItemById(Uuid $uuid): ?OrderTicketItemResponse
    {
        /** @var OrderTicketItemResponse|null $result */
        $result = $this->queryBus->ask(new OrderIdQuery($uuid));

        return $result;
    }
}
