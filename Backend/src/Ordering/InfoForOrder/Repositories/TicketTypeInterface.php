<?php

declare(strict_types=1);

namespace Tickets\Ordering\InfoForOrder\Repositories;

use Tickets\Ordering\InfoForOrder\Response\TicketTypeDto;

interface TicketTypeInterface
{
    /**
     * @return TicketTypeDto[]
     */
    public function getList(): array;
}
