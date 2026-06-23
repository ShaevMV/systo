<?php

declare(strict_types=1);

namespace Baza\Festival\Applications\ListFestivals;

use Baza\Shared\Domain\Bus\Query\Response;

class FestivalListResponse implements Response
{
    /**
     * @param  array<int, array<string, mixed>>  $festivals
     */
    public function __construct(
        private array $festivals,
    ) {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getFestivals(): array
    {
        return $this->festivals;
    }
}
