<?php

declare(strict_types=1);

namespace Tickets\History\Application\GetHistory;

use Shared\Domain\Bus\Query\Query;

class GetOrderHistoryQuery implements Query
{
    public function __construct(
        public readonly string $aggregateId,
    ) {
    }
}
