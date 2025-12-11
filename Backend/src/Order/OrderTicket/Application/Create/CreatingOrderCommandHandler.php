<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Application\Create;

use Throwable;
use Tickets\Order\OrderTicket\Repositories\OrderTicketRepositoryInterface;

final class CreatingOrderCommandHandler
{
    public function __construct(
        private OrderTicketRepositoryInterface $orderTicket,
    )
    {
    }

    /**
     * @throws Throwable
     */
    public function __invoke(CreatingOrderCommand $command): void
    {
        $this->orderTicket->create($command->getOrderTicketDto());
    }
}
