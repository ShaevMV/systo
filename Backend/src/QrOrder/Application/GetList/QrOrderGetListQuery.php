<?php

declare(strict_types=1);

namespace Tickets\QrOrder\Application\GetList;

use Shared\Domain\Bus\Query\Query;
use Shared\Domain\Criteria\Order;

/**
 * Запрос списка qr-заказов для админки: фильтр (whitelist полей внутри хендлера),
 * сортировка и страница пагинации.
 */
class QrOrderGetListQuery implements Query
{
    /**
     * @param array<string, mixed> $filter
     */
    public function __construct(
        private array $filter,
        private Order $orderBy,
        private int $page,
        private int $perPage,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getFilter(): array
    {
        return $this->filter;
    }

    public function getOrderBy(): Order
    {
        return $this->orderBy;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function getPerPage(): int
    {
        return $this->perPage;
    }
}
