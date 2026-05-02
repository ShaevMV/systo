<?php

declare(strict_types=1);

namespace Tickets\Orders\Guest\Repository;

use Shared\Domain\ValueObject\Uuid;
use Tickets\Orders\Guest\Domain\GuestOrder;
use Tickets\Orders\Guest\Dto\GuestOrderDto;

interface GuestOrderRepositoryInterface
{
    /** Создаёт запись в БД. Возвращает присвоенный kilter. */
    public function create(GuestOrderDto $dto): int;

    /** Загружает агрегат по ID. */
    public function findById(Uuid $id): ?GuestOrder;

    /** Сохраняет изменённое состояние агрегата (статус, билеты). */
    public function save(GuestOrder $order): void;
}
