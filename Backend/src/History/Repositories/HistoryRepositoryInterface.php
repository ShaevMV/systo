<?php

declare(strict_types=1);

namespace Tickets\History\Repositories;

use Tickets\History\Dto\DomainHistoryDto;
use Tickets\History\Dto\SaveHistoryDto;

interface HistoryRepositoryInterface
{
    public function save(SaveHistoryDto $dto): void;

    /** @return DomainHistoryDto[] */
    public function getByAggregateId(string $aggregateId): array;
}
