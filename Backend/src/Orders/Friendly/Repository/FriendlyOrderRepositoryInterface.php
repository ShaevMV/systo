<?php

declare(strict_types=1);

namespace Tickets\Orders\Friendly\Repository;

use Shared\Domain\ValueObject\Uuid;
use Tickets\Orders\Friendly\Domain\FriendlyOrder;
use Tickets\Orders\Friendly\Dto\FriendlyOrderDto;

interface FriendlyOrderRepositoryInterface
{
    /** Создаёт запись в БД. Возвращает присвоенный kilter. */
    public function create(FriendlyOrderDto $dto): int;

    /** Загружает агрегат по ID. */
    public function findById(Uuid $id): ?FriendlyOrder;

    /** Сохраняет изменённое состояние агрегата (статус, билеты). */
    public function save(FriendlyOrder $order): void;
}
