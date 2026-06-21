<?php

declare(strict_types=1);

namespace Baza\Tickets\Responses;

use Baza\Shared\Domain\Bus\Query\Response;

/**
 * Страница синка blacklist (Ф5, PR-6) + курсор. Аналог SnapshotPageResponse.
 */
final class BlacklistPageResponse implements Response
{
    /**
     * @param  BlacklistItemResponse[]  $items
     */
    public function __construct(
        private readonly array $items,
        private readonly int $nextAfterId,
        private readonly bool $hasMore,
    ) {}

    /** @return BlacklistItemResponse[] */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'items' => array_map(
                static fn (BlacklistItemResponse $item): array => $item->toArray(),
                $this->items,
            ),
            'next_after_id' => $this->nextAfterId,
            'has_more' => $this->hasMore,
            'count' => count($this->items),
        ];
    }
}
