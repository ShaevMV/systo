<?php

declare(strict_types = 1);

namespace Tickets\Order\OrderTicket\Repositories;

use Shared\Domain\Criteria\Filters;
use Shared\Domain\ValueObject\Status;
use Shared\Domain\ValueObject\Uuid;
use Tickets\Order\OrderTicket\Dto\OrderTicket\OrderTicketDto;
use Tickets\Order\OrderTicket\Responses\OrderTicketItemForFriendlyListResponse;
use Tickets\Order\OrderTicket\Responses\OrderTicketItemForListResponse;
use Tickets\Order\OrderTicket\Responses\OrderTicketItemForListsResponse;
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
     * Получить список заказов по фильтру для френдли продовца
     *
     * @param  Filters  $filters
     * @return OrderTicketItemForFriendlyListResponse[]
     */
    public function getFriendlyList(Filters $filters): array;

    /**
     * Сменить статус заказа
     *
     * @param Uuid $orderId
     * @param Status $newStatus
     * @param array $guests
     * @return bool
     */
    public function changeStatus(
        Uuid $orderId,
        Status $newStatus,
        array $guests
    ): bool;

    /**
     * Обновить список гостей без смены статуса
     *
     * @param Uuid $orderId
     * @param array $guests
     * @return bool
     */
    public function updateGuests(Uuid $orderId, array $guests): bool;

    /**
     * Изменить цену заказа (только для admin)
     *
     * @param Uuid $orderId
     * @param float $newPrice
     * @return bool
     */
    public function changePrice(Uuid $orderId, float $newPrice): bool;

    /**
     * Список заказов-списков (для admin / manager).
     *
     * @return OrderTicketItemForListsResponse[]
     */
    public function getListsList(Filters $filters): array;

    /**
     * Список заказов-списков для конкретного куратора.
     *
     * @return OrderTicketItemForListsResponse[]
     */
    public function getCuratorList(Filters $filters): array;
}
