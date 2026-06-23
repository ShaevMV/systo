<?php

declare(strict_types=1);

namespace Baza\Changes\Applications\Report;

use Baza\Shared\Domain\Bus\Query\Query;

class ReportForChangesQuery implements Query
{
    public function __construct(
        private string $festivalId,
    )
    {
    }

    public function getFestivalId(): string
    {
        return $this->festivalId;
    }
}
