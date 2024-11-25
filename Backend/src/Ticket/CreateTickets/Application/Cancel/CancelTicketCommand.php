<?php

declare(strict_types = 1);

namespace Tickets\Ticket\CreateTickets\Application\Cancel;

use Shared\Domain\Bus\Command\Command;
use Shared\Domain\ValueObject\Uuid;

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
