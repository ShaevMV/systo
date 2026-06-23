<?php

declare(strict_types=1);

namespace Baza\Festival\Applications\ListFestivals;

use Baza\Shared\Domain\Bus\Query\Query;

class ListFestivalsQuery implements Query
{
    public function __construct(
        private bool $onlyActiveForKpp = false,
    ) {
    }

    public function onlyActiveForKpp(): bool
    {
        return $this->onlyActiveForKpp;
    }
}
