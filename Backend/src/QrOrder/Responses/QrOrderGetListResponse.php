<?php

declare(strict_types=1);

namespace Tickets\QrOrder\Responses;

use Illuminate\Support\Collection;
use Shared\Domain\Bus\Query\Response;

/**
 * Ответ списка qr-заказов: страница элементов + общее количество по фильтрам (для пагинации).
 */
class QrOrderGetListResponse implements Response
{
    public function __construct(
        private Collection $collection,
        private int $totalCount,
    ) {}

    public function getCollection(): Collection
    {
        return $this->collection;
    }

    public function getTotalCount(): int
    {
        return $this->totalCount;
    }
}
