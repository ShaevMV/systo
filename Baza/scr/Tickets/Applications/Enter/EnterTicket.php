<?php

declare(strict_types=1);

namespace Baza\Tickets\Applications\Enter;

use Baza\Shared\Domain\Bus\Command\CommandBus;
use Baza\Shared\Infrastructure\Bus\Command\InMemorySymfonyCommandBus;
use Baza\Shared\Services\DefineService;
use Baza\Tickets\Applications\Enter\DrugTicket\DrugTicketCommand;
use Baza\Tickets\Applications\Enter\DrugTicket\DrugTicketCommandHandler;
use Baza\Tickets\Applications\Enter\ElTicket\ElTicketCommand;
use Baza\Tickets\Applications\Enter\ElTicket\ElTicketCommandHandler;
use Baza\Tickets\Applications\Enter\LiveTicket\LiveTicketCommand;
use Baza\Tickets\Applications\Enter\LiveTicket\LiveTicketCommandHandler;
use Baza\Tickets\Applications\Enter\SpisokTicket\SpisokTicketCommand;
use Baza\Tickets\Applications\Enter\SpisokTicket\SpisokTicketCommandHandler;

use Throwable;

class EnterTicket
{
    private CommandBus $bus;

    public function __construct(
        SpisokTicketCommandHandler $spisokTicketCommandHandler,
        ElTicketCommandHandler     $elTicketCommandHandler,
        DrugTicketCommandHandler   $drugTicketCommandHandler,
        LiveTicketCommandHandler   $liveTicketCommandHandler,
    )
    {
        $this->bus = new InMemorySymfonyCommandBus([
            SpisokTicketCommand::class => $spisokTicketCommandHandler,
            ElTicketCommand::class => $elTicketCommandHandler,
            DrugTicketCommand::class => $drugTicketCommandHandler,
            LiveTicketCommand::class => $liveTicketCommandHandler,
        ]);
    }

    /**
     * @throws Throwable
     */
    public function skip(string $type, int $id, int $changeId): void
    {
        $command = match ($type) {
            DefineService::SPISOK_TICKET => new SpisokTicketCommand($id, $changeId),
            DefineService::ELECTRON_TICKET => new ElTicketCommand($id, $changeId),
            DefineService::DRUG_TICKET => new DrugTicketCommand($id, $changeId),
            DefineService::LIVE_TICKET => new LiveTicketCommand($id, $changeId),
            default => throw new \DomainException('Не верный тип ' . $type),
        };

        $this->bus->dispatch($command);
    }
}
