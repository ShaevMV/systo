<?php

declare(strict_types=1);

namespace Baza\Ingest\Repositories;

/**
 * Приём билетов от org через ingest-API (Ф3).
 *
 * Зеркало того, что org раньше делал прямой записью в БД Baza (соединение mysqlBaza,
 * методы setInBaza*). Теперь org шлёт билет HTTP-запросом, а запись в свои таблицы делает
 * сама Baza — через этот репозиторий (БД только здесь). Идемпотентность по естественному
 * ключу каждой цели: el → uuid, spisok → ticket_uuid, live → kilter, auto → (order_id, auto).
 */
interface IngestRepositoryInterface
{
    /** el_tickets: INSERT по uuid, если нет; иначе UPDATE. Всегда true при успехе. */
    public function upsertElTicket(array $fields): bool;

    /** spisok_tickets: INSERT по ticket_uuid, если нет; иначе UPDATE. Всегда true при успехе. */
    public function upsertSpisokTicket(array $fields): bool;

    /**
     * live_tickets: UPDATE el_ticket_id (+ festival_id связанного el, TD-48 PR-6) по kilter
     * (строка создаётся диапазоном в Baza заранее). false, если строки с таким kilter нет —
     * org откатится на прямую запись/ретрай.
     */
    public function linkLiveTicket(int $kilter, ?string $elTicketId, ?string $festivalId = null): bool;

    /** auto: updateOrInsert по (order_id, auto) — идемпотентно. */
    public function upsertAuto(array $fields): bool;
}
