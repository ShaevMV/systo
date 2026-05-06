<?php

declare(strict_types=1);

namespace Tickets\Auto\Repositories;

use Shared\Domain\ValueObject\Uuid;
use Tickets\Auto\Dto\AutoDto;

interface AutoRepositoryInterface
{
    public function create(AutoDto $auto): bool;

    public function delete(Uuid $autoId): bool;

    public function getById(Uuid $autoId): ?AutoDto;

    /** @return AutoDto[] */
    public function getByOrderId(Uuid $orderTicketId): array;

    /**
     * Запись авто в таблицу `auto` базы Baza. Включает order_id для связи.
     */
    public function setInBazaAuto(AutoDto $auto, ?Uuid $festivalId): bool;

    /**
     * Удалить из Baza все авто конкретного заказа (по order_id).
     */
    public function removeAllFromBazaByOrderId(Uuid $orderId): bool;
}
