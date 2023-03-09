<?php

declare(strict_types=1);

namespace Tickets\Ticket\CreateTickets\Repositories;

use App\Models\Tickets\TicketFriendlyModel;

class InMemoryMySqlTicketsFriendlyRepository extends InMemoryMySqlTicketsRepository
{
    public function __construct(
        TicketFriendlyModel $model
    )
    {
        $this->model = $model;
    }
}
