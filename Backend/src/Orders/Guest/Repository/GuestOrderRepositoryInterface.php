<?php

declare(strict_types=1);

namespace Tickets\Orders\Guest\Repository;

use Shared\Domain\ValueObject\Uuid;
use Tickets\Orders\Guest\Domain\GuestOrder;
use Tickets\Orders\Guest\Dto\GuestOrderDto;
use Tickets\Orders\Shared\Response\OrderItemResponse;
use Tickets\Orders\Shared\Response\OrderListItemResponse;

interface GuestOrderRepositoryInterface
{
    /** Создаёт запись в БД. Возвращает присвоенный kilter. */
    public function create(GuestOrderDto $dto): int;

    /** Загружает агрегат по ID (для доменных операций). */
    public function findById(Uuid $id): ?GuestOrder;

    /** Сохраняет изменённое состояние агрегата (статус, билеты). */
    public function save(GuestOrder $order): void;

    /** Детали заказа для API-ответа (с JOIN на ticket_type, users). */
    public function getItem(Uuid $id): ?OrderItemResponse;

    /** Список заказов пользователя. */
    public function getUserList(Uuid $userId): array;

    /** Список всех заказов для admin/seller. @return OrderListItemResponse[] */
    public function getList(?string $status = null, ?Uuid $festivalId = null): array;
}
