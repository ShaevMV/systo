<?php

declare(strict_types=1);

namespace Baza\Tickets\Applications\Blacklist;

use Baza\Shared\Domain\Bus\Query\Query;

/**
 * Запрос порции синка чёрного списка отозванных билетов (Ф5, PR-6).
 */
class GetBlacklistQuery implements Query
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
