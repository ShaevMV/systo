<?php

declare(strict_types = 1);

namespace Tickets\Order\Shared\Repositories;

use Tickets\Order\OrderTicket\Domain\OrderTicketDto;
use Tickets\Order\OrderTicket\Responses\OrderTicketItemForListResponse;
use Tickets\Order\Shared\Domain\BaseOrderTicketDto;
use Tickets\Order\Shared\Responses\BaseOrderTicketItemResponse;
use Tickets\Shared\Domain\Criteria\Filters;
use Tickets\Shared\Domain\ValueObject\Status;
use Tickets\Shared\Domain\ValueObject\Uuid;

interface OrderTicketRepositoryInterface
{
    /**
     * Создать заказ
     */
    public function create(BaseOrderTicketDto $orderTicketDto): bool;

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
    public function findOrder(Uuid $uuid): ?BaseOrderTicketDto;

    /**
     * Вывести один заказ для пользователя
     *
     * @param  Uuid  $uuid
     * @return BaseOrderTicketItemResponse|null
     */
    public function getItem(Uuid $uuid): ?BaseOrderTicketItemResponse;

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
