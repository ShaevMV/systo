<?php

declare(strict_types=1);

namespace Baza\Tickets\Repositories;

use Baza\Tickets\Responses\SnapshotPageResponse;
use Baza\Tickets\Responses\TicketSearchResponse;

/**
 * Поисковый индекс билетов ticket_search (ручной поиск на КПП без QR + офлайн-снимок). БД только здесь.
 *
 * Наполняется из ingest (org→Baza), ищется на экране `/search`. Найдя — впуск по type+kilter.
 * Тот же индекс отдаёт минимизированный офлайн-снимок для PWA-сканера (Ф5, PR-3).
 */
interface TicketSearchRepositoryInterface
{
    /**
     * Записать/обновить строку индекса (идемпотентно по ticket_uuid).
     *
     * @param  array<string, mixed>  $row  ticket_uuid,festival_id,type,kilter + проекция + payload
     */
    public function index(array $row): bool;

    /**
     * Поиск по строке $q (LIKE по проекции: ФИО/телефон/телега/email/госномер/имя ребёнка/
     * телефон родителя/№ заказа; либо точный kilter). Фильтр по festival.
     *
     * @return TicketSearchResponse[]
     */
    public function find(string $q): array;

    /**
     * Порция офлайн-снимка билетов фестиваля (Ф5, PR-3) — для IndexedDB телефона.
     *
     * Минимизация ПДн (B5): возвращаются только поля впуска (uuid/kilter/тип/цвет/имя).
     *
     * Две оси курсора (клиент НЕ смешивает их в одном проходе):
     *  - **Полный снимок** (первый раз): `since = null`, пагинация по `id` (`afterId` растёт
     *    от 0 порциями, пока `has_more`).
     *  - **Дельта** (инкремент): `since = server_time предыдущего синка`, `afterId = 0` —
     *    забираем всё изменённое с момента since (updated_at >=). Дедуп по uuid на клиенте.
     *
     * @param  string|null  $festivalId  null → текущий фестиваль по умолчанию
     * @param  string|null  $since        datetime в TZ приложения: только изменённые с этого момента
     * @param  int  $afterId  курсор по id (брать строки с id > afterId)
     * @param  int  $limit  размер порции (зажимается к безопасному максимуму)
     */
    public function snapshot(?string $festivalId, ?string $since, int $afterId, int $limit): SnapshotPageResponse;
}
