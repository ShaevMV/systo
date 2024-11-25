<?php

declare(strict_types=1);

namespace Baza\Changes\Applications\OpenAndClose;

use Baza\Changes\Applications\OpenAndClose\Close\CloseChangeCommand;
use Baza\Changes\Applications\OpenAndClose\Close\CloseChangeCommandHandler;
use Baza\Shared\Domain\Bus\Command\CommandBus;
use Baza\Shared\Infrastructure\Bus\Command\InMemorySymfonyCommandBus;
use Throwable;

class OpenAndCloseChanges
{
    private CommandBus $bus;

    public function __construct(
        CloseChangeCommandHandler $closeChangeCommandHandler,
    )
    {
        $this->bus= new InMemorySymfonyCommandBus([
            CloseChangeCommand::class => $closeChangeCommandHandler,
        ]);
    }

    /**
     * @throws Throwable
     */
    public function close(int $userId): void
    {
        $this->bus->dispatch(new CloseChangeCommand($userId));
    }
}
