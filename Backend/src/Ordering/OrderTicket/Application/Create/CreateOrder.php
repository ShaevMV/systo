<?php

declare(strict_types = 1);

namespace Tickets\Ordering\OrderTicket\Application\Create;

use Throwable;
use Tickets\Ordering\InfoForOrder\Application\GetPriceByTicketType\GetPriceByTicketType;
use Tickets\Ordering\OrderTicket\Dto\OrderTicketDto;
use Tickets\Shared\Infrastructure\Bus\Command\InMemorySymfonyCommandBus;

final class CreateOrder
{
    private InMemorySymfonyCommandBus $commandBus;

    public function __construct(
        CreatingOrderCommandHandler $creatingOrderCommandHandler,
    ) {
        $this->commandBus = new InMemorySymfonyCommandBus([
            CreatingOrderCommand::class => $creatingOrderCommandHandler
        ]);
    }

    /**
     * @throws Throwable
     */
    public function creating(OrderTicketDto $orderTicketDto): void
    {
        $this->commandBus->dispatch(new CreatingOrderCommand($orderTicketDto));
    }
}
