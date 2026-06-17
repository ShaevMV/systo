<?php

declare(strict_types=1);

namespace Tickets\EmailDelivery\Application\GetList;

use Shared\Domain\Bus\Query\Query;
use Shared\Domain\Criteria\Order;

/**
 * Запрос списка писем для админки «Доставка писем»: фильтр (whitelist в хендлере),
 * сортировка и страница пагинации.
 */
class EmailMessageGetListQuery implements Query
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
