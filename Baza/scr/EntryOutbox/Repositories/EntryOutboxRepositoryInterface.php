<?php

declare(strict_types=1);

namespace Baza\EntryOutbox\Repositories;

use Baza\EntryOutbox\Dto\EntryOutboxDto;

/**
 * Буфер вебхука «билет прошёл» Baza→org (Ф4). БД только здесь.
 *
 * При впуске пишем запись (`enqueue`, идемпотентно по `(target, ticket_uuid)` — билет входит
 * один раз). Дренаж читает `pending`, шлёт на org и двигает статус.
 */
interface EntryOutboxRepositoryInterface
{
    /**
     * Записать факт входа билета (идемпотентно по `(target, ticket_uuid)`).
     * Возвращает false, если нечего записывать (нет ticket_uuid — org не сможет связать).
     */
    public function enqueue(
        string $target,
        ?string $ticketUuid,
        ?int $kilter,
        ?int $changeId,
        string $enteredAt,
        ?string $wristbandQr = null,
    ): bool;

    /**
     * Разрешить идентификатор билета в org по типу впуска и id.
     * el/spisok/live ищутся по kilter; auto — по id строки. Возвращает uuid (org) или null.
     */
    public function resolveTicketUuid(string $type, int $id): ?string;

    /** Маппинг типа впуска (DefineService) в target outbox; null — тип без вебхука. */
    public function targetForType(string $type): ?string;

    /**
     * Записи к отправке: `pending` или `failed` с attempts ниже капа. Старые — первыми.
     *
     * @return EntryOutboxDto[]
     */
    public function pending(int $limit, int $maxAttempts): array;

    public function markSending(string $id): bool;

    public function markSent(string $id): bool;

    public function markFailed(string $id, string $error): bool;
}
