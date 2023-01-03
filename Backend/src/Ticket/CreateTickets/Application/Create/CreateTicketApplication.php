<?php

namespace Tickets\Ticket\CreateTickets\Application\Create;

use Illuminate\Support\Facades\Bus;
use Throwable;
use Tickets\Shared\Domain\ValueObject\Uuid;
use Tickets\Shared\Infrastructure\Bus\Command\InMemorySymfonyCommandBus;
use Tickets\Ticket\CreateTickets\Domain\Ticket;
use Tickets\Ticket\CreateTickets\Dto\TicketDto;

class CreateTicketApplication
{
    private InMemorySymfonyCommandBus $commandBus;

    public function __construct(
        CreateTicketCommandHandler $commandHandler,
        private Bus $bus
    ) {
        $this->commandBus = new InMemorySymfonyCommandBus([
            CreateTicketCommand::class => $commandHandler
        ]);
    }

    /**
     * @return Ticket[]
     * @throws Throwable
     */
    public function createList(Uuid $orderId, array $guests): array
    {
        $tickets = [];
        foreach ($guests as $guest) {
            $ticket = Ticket::newTicket($orderId, $guest);

            $this->commandBus->dispatch(new CreateTicketCommand(
                new TicketDto(
                    $orderId,
                    $guest,
                    $ticket->getAggregateId()
                )
            ));

            $this->bus::chain($ticket->pullDomainEvents())
                ->dispatch();
            $tickets[] = $ticket;
        }

        return $tickets;
    }
}
