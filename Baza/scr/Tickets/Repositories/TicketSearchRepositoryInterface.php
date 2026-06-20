<?php

declare(strict_types=1);

namespace Baza\Tickets\Repositories;

use Baza\Tickets\Responses\TicketSearchResponse;

/**
 * Поисковый индекс билетов ticket_search (ручной поиск на КПП без QR). БД только здесь.
 *
 * Наполняется из ingest (org→Baza), ищется на экране `/search`. Найдя — впуск по type+kilter.
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
}
