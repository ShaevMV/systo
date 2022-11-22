<?php

declare(strict_types=1);

namespace Tickets\Ordering\InfoForOrder\Repositories;

use App\Models\Tickets\Ordering\InfoForOrder\Models\TicketTypes;
use Tickets\Ordering\InfoForOrder\Response\TicketTypeDto;

final class InMemoryMySqlTicketType implements TicketTypeInterface
{
    public function __construct(
        private TicketTypes $model,
    ) {
    }

    public function getList(): array
    {
        $result = [];
        foreach ($this->model::all() as $item) {
            $result[] = TicketTypeDto::fromState($item->toArray());
        }

        return $result;
    }
}
