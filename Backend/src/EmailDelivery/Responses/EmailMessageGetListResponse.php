<?php

declare(strict_types=1);

namespace Tickets\EmailDelivery\Responses;

use Illuminate\Support\Collection;
use Shared\Domain\Bus\Query\Response;

/**
 * Страница списка писем + total для пагинации админки.
 */
final class EmailMessageGetListResponse implements Response
{
    public function __construct(
        private readonly Collection $collection,
        private readonly int $totalCount,
    ) {
    }

    public function getCollection(): Collection
    {
        return $this->collection;
    }

    public function getTotalCount(): int
    {
        return $this->totalCount;
    }
}
