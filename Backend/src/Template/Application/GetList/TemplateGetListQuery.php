<?php

declare(strict_types=1);

namespace Tickets\Template\Application\GetList;

use Shared\Domain\Bus\Query\Query;
use Shared\Domain\Criteria\Order;

/**
 * Запрос списка шаблонов для админки: фильтр (whitelist полей в хендлере) + сортировка.
 */
class TemplateGetListQuery implements Query
{
    /**
     * @param array<string, mixed> $filter
     */
    public function __construct(
        private array $filter,
        private Order $orderBy,
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
}
