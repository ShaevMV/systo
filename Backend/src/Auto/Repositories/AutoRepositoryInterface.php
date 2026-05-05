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
     * Запись авто в таблицу `auto` базы Baza.
     * Поля curator/project передаются строкой; festival_id — UUID.
     */
    public function setInBazaAuto(AutoDto $auto, string $curator, string $project, ?Uuid $festivalId): bool;

    /**
     * Удаление одной записи авто из таблицы `auto` базы Baza
     * по сигнатуре (festival_id, curator, project, auto). LIMIT 1.
     */
    public function removeFromBazaAuto(AutoDto $auto, string $curator, string $project, ?Uuid $festivalId): bool;
}
