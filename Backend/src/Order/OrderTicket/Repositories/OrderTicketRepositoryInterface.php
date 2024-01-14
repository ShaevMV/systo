<?php

declare(strict_types = 1);

namespace Tickets\Order\OrderTicket\Repositories;

use Shared\Domain\Criteria\Filters;
use Shared\Domain\ValueObject\Status;
use Shared\Domain\ValueObject\Uuid;
use Tickets\Order\OrderTicket\Dto\OrderTicket\OrderTicketDto;
use Tickets\Order\OrderTicket\Responses\OrderTicketItemForListResponse;
use Tickets\Order\OrderTicket\Responses\OrderTicketItemResponse;

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
     * @return OrderTicketItemForListResponse[]
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
     * Вывести один заказ для пользователя
     *
     * @param  Uuid  $uuid
     * @return OrderTicketItemResponse|null
     */
    public function getItem(Uuid $uuid): ?OrderTicketItemResponse;

    /**
     * Получить список заказов по фильтру
     *
     * @param  Filters  $filters
     * @return OrderTicketItemForListResponse[]
     */
    public function getList(Filters $filters): array;

    /**
     * Сменить статус заказа
     *
     * @param Uuid $orderId
     * @param Status $newStatus
     * @param array $guests
     * @return bool
     */
    public function chanceStatus(
        Uuid $orderId,
        Status $newStatus,
        array $guests
    ): bool;
}
