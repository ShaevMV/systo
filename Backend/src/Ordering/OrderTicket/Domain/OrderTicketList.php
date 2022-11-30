<?php

declare(strict_types=1);

namespace Tickets\Ordering\OrderTicket\Domain;

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

    public function toArray(): array
    {
        $result = [];

        foreach ($this->list as $item) {
            $result[] = $item->toArray();
        }

        return [
            'list' => $result
        ];
    }
}
