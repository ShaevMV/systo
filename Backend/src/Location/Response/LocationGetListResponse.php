<?php

declare(strict_types=1);

namespace Tickets\Location\Response;

use Illuminate\Support\Collection;
use Shared\Domain\Bus\Query\Response;

class LocationGetListResponse implements Response
{
    public function __construct(
        private Collection $collection
    ) {
    }

    public function getCollection(): Collection
    {
        return $this->collection;
    }
}
