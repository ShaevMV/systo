<?php

declare(strict_types=1);

namespace Baza\Tickets\Responses;

use Baza\Shared\Domain\Bus\Query\Response;

/**
 * Страница офлайн-снимка билетов (Ф5, PR-3) + курсор для следующей порции.
 *
 * Клиент тянет снимок порциями: держит `next_after_id` (курсор по id) и `server_time`
 * (высокая отметка для следующей дельты). `has_more` = есть ли ещё строки в этой выборке.
 */
final class SnapshotPageResponse implements Response
{
    /**
     * @param  SnapshotItemResponse[]  $items
     */
    public function __construct(
        private readonly array $items,
        private readonly int $nextAfterId,
        private readonly bool $hasMore,
    ) {}

    /** @return SnapshotItemResponse[] */
    public function getItems(): array
    {
        return $this->items;
    }

    public function getNextAfterId(): int
    {
        return $this->nextAfterId;
    }

    public function hasMore(): bool
    {
        return $this->hasMore;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'items' => array_map(
                static fn (SnapshotItemResponse $item): array => $item->toArray(),
                $this->items,
            ),
            'next_after_id' => $this->nextAfterId,
            'has_more' => $this->hasMore,
            'count' => count($this->items),
        ];
    }
}
