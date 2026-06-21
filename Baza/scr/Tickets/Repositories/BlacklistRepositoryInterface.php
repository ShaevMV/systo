<?php

declare(strict_types=1);

namespace Baza\Tickets\Repositories;

use Baza\Tickets\Responses\BlacklistPageResponse;

/**
 * Чёрный список отозванных билетов (Ф5, PR-6, B6). БД только здесь.
 *
 * Приём отзыва от org (ingest-канал) + синк на телефон + серверная проверка «отозван».
 */
interface BlacklistRepositoryInterface
{
    /**
     * Отозвать билет (идемпотентно по uuid; либо по (kilter, festival), если uuid нет).
     *
     * @return bool  false — нечего идентифицировать (нет ни uuid, ни kilter)
     */
    public function revoke(?string $ticketUuid, ?int $kilter, ?string $festivalId, ?string $reason): bool;

    /** Билет в чёрном списке (серверный гейт, дополнение к клиентскому). */
    public function isRevoked(?string $ticketUuid, ?int $kilter): bool;

    /**
     * Порция синка blacklist (дельта по updated_at, пагинация по id) — как снимок.
     */
    public function page(?string $festivalId, ?string $since, int $afterId, int $limit): BlacklistPageResponse;
}
