<?php

declare(strict_types=1);

namespace Tickets\Ordering\OrderTicket\Domain;

use JsonException;
use Tickets\Shared\Domain\Aggregate\AggregateRoot;

class OrderTicketList extends AggregateRoot
{
    /**
     * @param  OrderTicketItem[]  $list
     */
    public function __construct(
        private array $list
    ) {
    }

    /**
     * @throws JsonException
     */
    public function toArray(): array
    {
        $result = [];

        foreach ($this->list as $item) {
            $result[] = $item->toArray();
        }

        return $result;
    }
}
