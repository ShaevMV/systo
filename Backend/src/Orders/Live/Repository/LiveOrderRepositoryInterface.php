<?php

declare(strict_types=1);

namespace Tickets\Orders\Live\Repository;

use Shared\Domain\ValueObject\Uuid;
use Tickets\Orders\Live\Domain\LiveOrder;
use Tickets\Orders\Live\Dto\LiveOrderDto;

interface LiveOrderRepositoryInterface
{
    /** Создаёт запись в БД. Возвращает присвоенный kilter. */
    public function create(LiveOrderDto $dto): int;

    /** Загружает агрегат по ID. */
    public function findById(Uuid $id): ?LiveOrder;

    /** Сохраняет изменённое состояние агрегата (статус, билеты). */
    public function save(LiveOrder $order): void;
}
