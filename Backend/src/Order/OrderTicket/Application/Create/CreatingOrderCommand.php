<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Application\Create;

use Shared\Domain\Bus\Command\Command;
use Tickets\Order\OrderTicket\Dto\OrderTicket\OrderTicketDto;

final class CreatingOrderCommand implements Command
{
    public function __construct(
        private OrderTicketDto $orderTicketDto
    ) {
    }

    public function getOrderTicketDto(): OrderTicketDto
    {
        return $this->orderTicketDto;
    }
}
