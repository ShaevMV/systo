<?php

declare(strict_types=1);

namespace Baza\Tickets\Applications\Snapshot;

use Baza\Shared\Domain\Bus\Query\Query;

/**
 * Запрос порции офлайн-снимка билетов (Ф5, PR-3).
 *
 * @see \Baza\Tickets\Applications\Snapshot\GetSnapshotQueryHandler
 */
class GetSnapshotQuery implements Query
{
    public function __construct(
        private readonly ?string $festivalId,
        private readonly ?string $since,
        private readonly int $afterId,
        private readonly int $limit,
    ) {}

    public function getFestivalId(): ?string
    {
        return $this->festivalId;
    }

    public function getSince(): ?string
    {
        return $this->since;
    }

    public function getAfterId(): int
    {
        return $this->afterId;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }
}
