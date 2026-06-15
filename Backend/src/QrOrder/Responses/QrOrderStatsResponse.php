<?php

declare(strict_types=1);

namespace Tickets\QrOrder\Responses;

use Shared\Domain\Bus\Query\Response;

/**
 * Сводные метрики qr-заказов для дашборда: итоги + разрезы по статусу, типу заказа и дням.
 * Деньги (revenue) — целые рубли (как total_price в qr_orders).
 */
class QrOrderStatsResponse implements Response
{
    /**
     * @param array{orders: int, revenue: int} $totals
     * @param array<int, array{status: string, orders: int, revenue: int}> $byStatus
     * @param array<int, array{type_order: ?string, orders: int, revenue: int}> $byType
     * @param array<int, array{date: string, orders: int, revenue: int}> $timeseries
     */
    public function __construct(
        private array $totals,
        private array $byStatus,
        private array $byType,
        private array $timeseries,
    ) {
    }

    /**
     * @return array{
     *     totals: array{orders: int, revenue: int},
     *     byStatus: array<int, array{status: string, orders: int, revenue: int}>,
     *     byType: array<int, array{type_order: ?string, orders: int, revenue: int}>,
     *     timeseries: array<int, array{date: string, orders: int, revenue: int}>
     * }
     */
    public function toArray(): array
    {
        return [
            'totals' => $this->totals,
            'byStatus' => $this->byStatus,
            'byType' => $this->byType,
            'timeseries' => $this->timeseries,
        ];
    }
}
