<?php

declare(strict_types=1);

namespace Tickets\Order\OrderFriendly\Application\CreateOrder;

use Throwable;
use Tickets\Order\OrderFriendly\Domain\OrderTicketDto;
use Tickets\Shared\Infrastructure\Bus\Command\InMemorySymfonyCommandBus;

class CreateOrder
{
    private InMemorySymfonyCommandBus $commandBus;

    public function __construct(
        CreateOrderCommandHandler $createOrderCommandHandler
    )
    {
        $this->commandBus = new InMemorySymfonyCommandBus([
            CreateOrderCommand::class => $createOrderCommandHandler
        ]);
    }


    /**
     * @throws Throwable
     */
    public function create(OrderTicketDto $orderTicketDto): void
    {
        $this->commandBus->dispatch(new CreateOrderCommand($orderTicketDto));
    }
}
