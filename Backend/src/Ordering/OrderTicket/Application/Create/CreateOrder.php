<?php

declare(strict_types=1);

namespace Tickets\Ordering\OrderTicket\Application\Create;

use Illuminate\Support\Facades\Bus;
use Throwable;
use Tickets\Ordering\OrderTicket\Domain\OrderTicket;
use Tickets\Ordering\OrderTicket\Dto\OrderTicketDto;
use Tickets\Shared\Infrastructure\Bus\Command\InMemorySymfonyCommandBus;

final class CreateOrder
{
    private InMemorySymfonyCommandBus $commandBus;

    public function __construct(
        CreatingOrderCommandHandler $creatingOrderCommandHandler,
        private Bus $bus
    ) {
        $this->commandBus = new InMemorySymfonyCommandBus([
            CreatingOrderCommand::class => $creatingOrderCommandHandler
        ]);
    }

    /**
     * @throws Throwable
     */
    public function creating(OrderTicketDto $orderTicketDto, string $buyersMail): void
    {
        $orderTicket = OrderTicket::create($orderTicketDto->toArray(), $buyersMail);

        $this->commandBus->dispatch(new CreatingOrderCommand($orderTicketDto));

        $this->bus::chain($orderTicket->pullDomainEvents())
            ->dispatch();
    }
}
