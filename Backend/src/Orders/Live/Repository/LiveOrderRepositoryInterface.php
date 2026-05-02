<?php

declare(strict_types=1);

namespace Tickets\Orders\Live\Repository;

use Shared\Domain\ValueObject\Uuid;
use Tickets\Orders\Live\Domain\LiveOrder;
use Tickets\Orders\Live\Dto\LiveOrderDto;
use Tickets\Orders\Shared\Response\OrderItemResponse;
use Tickets\Orders\Shared\Response\OrderListItemResponse;

interface LiveOrderRepositoryInterface
{
    /** Создаёт запись в БД. Возвращает присвоенный kilter. */
    public function create(LiveOrderDto $dto): int;

    /** Загружает агрегат по ID. */
    public function findById(Uuid $id): ?LiveOrder;

    /** Сохраняет изменённое состояние агрегата (статус, билеты). */
    public function save(LiveOrder $order): void;

    /** Детали заказа для API-ответа (с JOIN на ticket_type, users). */
    public function getItem(Uuid $id): ?OrderItemResponse;

    /** Список заказов пользователя. */
    public function getUserList(Uuid $userId): array;

    /** Список всех заказов для admin/seller. @return OrderListItemResponse[] */
    public function getList(?string $status = null, ?Uuid $festivalId = null): array;
}
