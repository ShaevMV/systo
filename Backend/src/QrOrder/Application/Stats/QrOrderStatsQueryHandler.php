<?php

declare(strict_types=1);

namespace Tickets\QrOrder\Application\Stats;

use Tickets\QrOrder\Repositories\QrOrderRepositoryInterface;
use Tickets\QrOrder\Responses\QrOrderStatsResponse;
use Shared\Domain\Bus\Query\QueryHandler;

/**
 * Считает сводные метрики qr-заказов для дашборда: заказы + выручка, всего и в разрезах
 * (по статусу, по типу заказа, по дням). Сами агрегаты — в репозитории (БД только там, правило №1).
 *
 * Whitelist фильтров: только festival_id и диапазон дат уходят в запрос — клиент не может
 * фильтровать по произвольным полям.
 */
class QrOrderStatsQueryHandler implements QueryHandler
{
    public function __construct(
        private QrOrderRepositoryInterface $repository,
    ) {
    }

    public function __invoke(QrOrderStatsQuery $query): QrOrderStatsResponse
    {
        $filter = $query->getFilter();

        $raw = $this->repository->aggregateStats([
            'festival_id' => $filter['festival_id'] ?? null,
            'date_from' => $filter['date_from'] ?? null,
            'date_to' => $filter['date_to'] ?? null,
        ]);

        return new QrOrderStatsResponse(
            $raw['totals'],
            $raw['byStatus'],
            $raw['byType'],
            $raw['timeseries'],
        );
    }
}
