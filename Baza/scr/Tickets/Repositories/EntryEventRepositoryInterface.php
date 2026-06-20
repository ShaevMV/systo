<?php

declare(strict_types=1);

namespace Baza\Tickets\Repositories;

/**
 * Append-only журнал проходов (Ф5, PR-8). БД только здесь.
 */
interface EntryEventRepositoryInterface
{
    /** Намерение с таким client_op_id уже записано (идемпотентность дренажа). */
    public function existsByClientOp(string $clientOpId): bool;

    /**
     * Есть ли уже НЕ-дубликатный (засчитанный) впуск этого билета — для «первый впуск побеждает».
     * Сверка по (type+kilter); при наличии uuid — дополнительно по нему.
     */
    public function firstEntryExists(string $type, ?int $kilter, ?string $ticketUuid): bool;

    /**
     * Добавить запись в журнал (только append, без перезаписи).
     *
     * @param  array<string, mixed>  $fields
     */
    public function append(array $fields): bool;
}
