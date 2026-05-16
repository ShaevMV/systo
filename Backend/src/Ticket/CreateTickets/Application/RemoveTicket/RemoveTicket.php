<?php

declare(strict_types=1);

namespace Tickets\Ticket\CreateTickets\Application\RemoveTicket;

use Shared\Domain\ValueObject\Uuid;
use Shared\Infrastructure\Bus\Command\InMemorySymfonyCommandBus;
use Throwable;
use Tickets\Ticket\CreateTickets\Application\ChangeTicket\ChangeTicketCommand;

class RemoveTicket
{
    private InMemorySymfonyCommandBus $commandBus;

    public function __construct(RemoveTicketCommandHandler $commandHandler)
    {
        $this->commandBus = new InMemorySymfonyCommandBus([
            RemoveTicketCommand::class => $commandHandler,
        ]);
    }

    /**
     * @param array $valueMap [ticketId => newValue]
     * @param array $emailMap [ticketId => newEmail]
     * @throws Throwable
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

    public function remove(
        Uuid    $orderId,
        Uuid   $ticketId,
        ?string $actorId = null,
    ): void {
        $this->commandBus->dispatch(new RemoveTicketCommand(
            $orderId,
            $ticketId,
            $actorId,
        ));
    }
}
