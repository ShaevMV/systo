<?php

declare(strict_types=1);

namespace Tickets\Ticket\CreateTickets\Application;

use DomainException;
use Nette\Utils\JsonException;
use Throwable;
use Tickets\Shared\Domain\Bus\Command\CommandBus;
use Tickets\Shared\Domain\ValueObject\Uuid;
use Tickets\Shared\Infrastructure\Bus\Command\InMemorySymfonyCommandBus;
use Tickets\Ticket\CreateTickets\Application\PushTicket\PushTicketsCommand;
use Tickets\Ticket\CreateTickets\Application\PushTicket\PushTicketsCommandHandler;
use Tickets\Ticket\CreateTickets\Repositories\TicketsRepositoryInterface;

class PushTicket
{
    private CommandBus $commandBus;

    public function __construct(
        PushTicketsCommandHandler $handler,
        private TicketsRepositoryInterface $ticketsRepository,
    ){
        $this->commandBus = new InMemorySymfonyCommandBus([
            PushTicketsCommand::class => $handler
        ]);
    }


    /**
     * @throws JsonException
     * @throws DomainException
     * @throws Throwable
     */
    public function pushTicket(Uuid $id): void
    {
        $this->commandBus->dispatch(new PushTicketsCommand($id));
    }

    /**
     * @throws Throwable
     * @throws JsonException
     */
    public function pushByOrderId(Uuid $orderId): void
    {
        $idTickets = $this->ticketsRepository->getListIdByOrderId($orderId, true);
        foreach ($idTickets as $id) {
            $this->pushTicket($id);
        }
    }
}
