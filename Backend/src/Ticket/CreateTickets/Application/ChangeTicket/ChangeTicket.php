<?php

declare(strict_types=1);

namespace Tickets\Ticket\CreateTickets\Application\ChangeTicket;

use Shared\Domain\ValueObject\Uuid;
use Shared\Infrastructure\Bus\Command\InMemorySymfonyCommandBus;

class ChangeTicket
{
    private InMemorySymfonyCommandBus $commandBus;

    public function __construct(ChangeTicketCommandHandler $commandHandler)
    {
        $this->commandBus = new InMemorySymfonyCommandBus([
            ChangeTicketCommand::class => $commandHandler,
        ]);
    }

    /**
     * @param array $valueMap [ticketId => newValue]
     * @param array $emailMap [ticketId => newEmail]
     */
    public function change(
        Uuid    $orderId,
        array   $valueMap,
        array   $emailMap,
        ?string $actorId = null,
    ): void {
        $this->commandBus->dispatch(new ChangeTicketCommand(
            $orderId,
            $valueMap,
            $emailMap,
            $actorId,
        ));
    }
}
