<?php

namespace Tickets\Order\OrderFriendly\Application\CreateOrder;

use Tickets\Order\OrderFriendly\Domain\OrderTicketDto;
use Tickets\Shared\Domain\Bus\Command\Command;

class CreateOrderCommand implements Command
{
    public function __construct(
        private OrderTicketDto $orderTicketDto
    ){
    }

    public function getOrderTicketDto(): OrderTicketDto
    {
        return $this->orderTicketDto;
    }
}
