<?php

declare(strict_types = 1);

namespace Tickets\Order\OrderTicket\Application\Create;

use Tickets\Order\OrderTicket\Repositories\OrderTicketRepositoryInterface;

final class CreatingOrderCommandHandler
{
    public function __construct(
        private OrderTicketRepositoryInterface $orderTicket
    ){
    }

    public function __invoke(CreatingOrderCommand $command): void
    {
        $this->orderTicket->create($command->getOrderTicketDto());
    }
}
