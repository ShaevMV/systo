<?php

declare(strict_types = 1);

namespace Tickets\Ordering\OrderTicket\Repositories;

use Tickets\Ordering\OrderTicket\Domain\OrderTicketItem;
use Tickets\Ordering\OrderTicket\Dto\OrderTicketDto;
use Tickets\Shared\Domain\Criteria\Filter;
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
     * @return OrderTicketItem[]
     */
    public function getUserList(Uuid $userId): array;

    /**
     * Поиск заказа по id
     *
     * @param  Uuid  $uuid
     * @return OrderTicketItem|null
     */
    public function findOrder(Uuid $uuid): ?OrderTicketItem;

    /**
     * Получить список заказов по фильтру
     *
     * @param  Filters  $filters
     * @return OrderTicketItem[]
     */
    public function getList(Filters $filters): array;


    public function getTotal(Filters $filters): array;

}
