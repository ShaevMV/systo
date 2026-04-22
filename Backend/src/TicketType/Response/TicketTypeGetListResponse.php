<?php

namespace Tickets\TicketType\Response;

use Illuminate\Support\Collection;
use Shared\Domain\Bus\Query\Response;
use Tickets\TicketType\Dto\TicketTypeDto;

class TicketTypeGetListResponse implements Response
{
    /**
     * @param Collection<TicketTypeDto> $collection
     */
    public function __construct(
        private Collection $collection
    )
    {
    }

    public function getCollection(): Collection
    {
        return $this->collection;
    }
}
