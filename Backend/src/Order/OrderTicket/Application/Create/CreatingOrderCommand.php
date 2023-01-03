<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Application\Create;

use Tickets\Order\OrderTicket\Dto\OrderTicket\OrderTicketDto;
use Tickets\Shared\Domain\Bus\Command\Command;

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
