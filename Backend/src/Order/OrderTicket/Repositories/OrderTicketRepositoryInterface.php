<?php

declare(strict_types = 1);

namespace Tickets\Order\OrderTicket\Repositories;

use Tickets\Order\OrderTicket\Dto\OrderTicket\OrderTicketDto;
use Tickets\Order\OrderTicket\Dto\OrderTicket\OrderTicketItemDto;
use Tickets\Order\OrderTicket\Responses\OrderTicketItemForList;
use Tickets\Shared\Domain\Criteria\Filters;
use Tickets\Shared\Domain\ValueObject\Uuid;

interface OrderTicketRepositoryInterface
{
    /**
     * Создать заказ
     *
     * @param  OrderTicketDto  $orderTicketDto
     * @return bool
     */
    public function create(OrderTicketDto $orderTicketDto): bool;

    /**
     * Получить список заказов у пользователя
     *
     * @param  Uuid  $userId
     * @return OrderTicketItemForList[]
     */
    public function getUserList(Uuid $userId): array;

    /**
     * Поиск заказа по id
     *
     * @param  Uuid  $uuid
     * @return OrderTicketDto|null
     */
    public function findOrder(Uuid $uuid): ?OrderTicketDto;

    /**
     * Получить список заказов по фильтру
     *
     * @param  Filters  $filters
     * @return OrderTicketItemForList[]
     */
    public function getList(Filters $filters): array;


    public function getTotal(Filters $filters): array;

}
