<?php

declare(strict_types = 1);

namespace Tickets\Ordering\OrderTicket\Repositories;

use Tickets\Ordering\OrderTicket\Dto\OrderTicketDto;

interface OrderTicketInterface
{
    public function create(OrderTicketDto $orderTicketDto): bool;
}
