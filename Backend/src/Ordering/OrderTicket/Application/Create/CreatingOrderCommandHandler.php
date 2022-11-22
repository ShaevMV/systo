<?php

declare(strict_types = 1);

namespace Tickets\Ordering\OrderTicket\Application\Create;

use Tickets\Ordering\OrderTicket\Repositories\OrderTicketInterface;

final class CreatingOrderCommandHandler
{
    public function __construct(
        private OrderTicketInterface $orderTicket
    ){
    }

    public function __invoke(CreatingOrderCommand $command): void
    {
        $this->orderTicket->create($command->getOrderTicketDto());
    }
}
