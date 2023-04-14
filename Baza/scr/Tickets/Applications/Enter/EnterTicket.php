<?php

declare(strict_types=1);

namespace Baza\Tickets\Applications\Enter;

use Baza\Shared\Domain\Bus\Command\CommandBus;
use Baza\Shared\Infrastructure\Bus\Command\InMemorySymfonyCommandBus;
use Baza\Tickets\Applications\Enter\ElTicket\ElTicketCommand;
use Baza\Tickets\Applications\Enter\ElTicket\ElTicketCommandHandler;
use Baza\Tickets\Applications\Enter\SpisokTicket\SpisokTicketCommand;
use Baza\Tickets\Applications\Enter\SpisokTicket\SpisokTicketCommandHandler;
use Baza\Tickets\Applications\Search\DefineService;
use Throwable;

class EnterTicket
{
    private CommandBus $bus;

    public function __construct(
        SpisokTicketCommandHandler $spisokTicketCommandHandler,
        ElTicketCommandHandler $elTicketCommandHandler,
    )
    {
        $this->bus = new InMemorySymfonyCommandBus([
            SpisokTicketCommand::class => $spisokTicketCommandHandler,
            ElTicketCommand::class => $elTicketCommandHandler,
        ]);
    }

    /**
     * @throws Throwable
     */
    public function skip(string $type, int $id, int $userId): void
    {
        $command = match ($type) {
            DefineService::SPISOK_TICKET => new SpisokTicketCommand($id, $userId),
            DefineService::ELECTRON_TICKET => new ElTicketCommand($id, $userId),
            default => throw new \DomainException('Не верный тип ' . $type),
        };

        $this->bus->dispatch($command);
    }
}
