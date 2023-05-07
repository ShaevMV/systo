<?php

declare(strict_types=1);

namespace Baza\Changes\Applications\AddTicketsInReport;

use Baza\Shared\Domain\Bus\Command\CommandBus;
use Baza\Shared\Infrastructure\Bus\Command\InMemorySymfonyCommandBus;
use Throwable;

class AddTicketsInReport
{
    private CommandBus $bus;

    public function __construct(
        AddTicketsInReportCommandHandler $addTicketsInReportCommandHandler
    )
    {
        $this->bus = new InMemorySymfonyCommandBus([
            AddTicketsInReportCommand::class => $addTicketsInReportCommandHandler
        ]);
    }

    /**
     * @throws Throwable
     */
    public function increment(int $changeId, string $typeTicket): void
    {
        $this->bus->dispatch(new AddTicketsInReportCommand($changeId, $typeTicket));
    }
}
