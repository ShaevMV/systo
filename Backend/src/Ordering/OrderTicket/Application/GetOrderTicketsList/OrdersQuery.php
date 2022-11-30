<?php

declare(strict_types=1);

namespace Tickets\Ordering\OrderTicket\Application\GetOrderTicketsList;

use Tickets\Shared\Domain\Bus\Query\Query;
use Tickets\Shared\Domain\ValueObject\Uuid;

class OrdersQuery implements Query
{
    public function __construct(
        private Uuid $userId
    ) {
    }

    public function getUserId(): Uuid
    {
        return $this->userId;
    }
}
