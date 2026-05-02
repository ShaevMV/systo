<?php

declare(strict_types=1);

namespace Tickets\Orders\Friendly\Repository;

use Shared\Domain\ValueObject\Uuid;
use Tickets\Orders\Friendly\Domain\FriendlyOrder;
use Tickets\Orders\Friendly\Dto\FriendlyOrderDto;
use Tickets\Orders\Shared\Response\OrderItemResponse;
use Tickets\Orders\Shared\Response\OrderListItemResponse;

interface FriendlyOrderRepositoryInterface
{
    /** Создаёт запись в БД. Возвращает присвоенный kilter. */
    public function create(FriendlyOrderDto $dto): int;

    /** Загружает агрегат по ID. */
    public function findById(Uuid $id): ?FriendlyOrder;

    /** Сохраняет изменённое состояние агрегата (статус, билеты). */
    public function save(FriendlyOrder $order): void;

    /** Детали заказа для API-ответа (с JOIN на ticket_type, users). */
    public function getItem(Uuid $id): ?OrderItemResponse;

    /** Список заказов пользователя. */
    public function getUserList(Uuid $userId): array;

    /** Список всех заказов для admin/pusher. @return OrderListItemResponse[] */
    public function getList(?string $status = null, ?Uuid $festivalId = null): array;
}
