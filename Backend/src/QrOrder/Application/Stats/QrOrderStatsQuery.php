<?php

declare(strict_types=1);

namespace Tickets\QrOrder\Application\Stats;

use Shared\Domain\Bus\Query\Query;

/**
 * Запрос сводных метрик qr-заказов для дашборда админки.
 * Фильтр — whitelist внутри хендлера (festival_id + диапазон дат created_at).
 */
class QrOrderStatsQuery implements Query
{
    /**
     * @param array<string, mixed> $filter
     */
    public function __construct(
        private array $filter,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getFilter(): array
    {
        return $this->filter;
    }
}
