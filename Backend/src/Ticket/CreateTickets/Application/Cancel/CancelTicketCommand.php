<?php

declare(strict_types = 1);

namespace Tickets\Ticket\CreateTickets\Application\Cancel;

use Tickets\Shared\Domain\Bus\Command\Command;
use Tickets\Shared\Domain\ValueObject\Uuid;

class CancelTicketCommand implements Command
{
    public function __construct(
       private Uuid $orderId,
    ){
    }

    public function getOrderId(): Uuid
    {
        return $this->orderId;
    }
}
