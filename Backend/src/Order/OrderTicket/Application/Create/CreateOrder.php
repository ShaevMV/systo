<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Application\Create;

use Bus;
use Throwable;
use Tickets\Order\OrderTicket\Domain\OrderTicket;
use Tickets\Order\OrderTicket\Dto\OrderTicket\OrderTicketDto;
use Tickets\Shared\Infrastructure\Bus\Command\InMemorySymfonyCommandBus;

final class CreateOrder
{
    private InMemorySymfonyCommandBus $commandBus;

    public function __construct(
        CreatingOrderCommandHandler $creatingOrderCommandHandler,
        private Bus $bus,
    ) {
        $this->commandBus = new InMemorySymfonyCommandBus([
            CreatingOrderCommand::class => $creatingOrderCommandHandler
        ]);
    }

    /**
     * @throws Throwable
     */
    public function createAndSave(OrderTicketDto $orderTicketDto): bool
    {
        $orderTicket = OrderTicket::create($orderTicketDto);

        $this->commandBus->dispatch(new CreatingOrderCommand($orderTicketDto));

        $this->bus::chain($orderTicket->pullDomainEvents())
            ->dispatch();

        return true;
    }
}
