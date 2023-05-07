<?php

declare(strict_types=1);

namespace Baza\Changes\Applications\OpenAndClose;

use Baza\Changes\Applications\OpenAndClose\Close\CloseChangeCommand;
use Baza\Changes\Applications\OpenAndClose\Close\CloseChangeCommandHandler;
use Baza\Changes\Applications\OpenAndClose\Open\OpenChangeCommand;
use Baza\Changes\Applications\OpenAndClose\Open\OpenChangeCommandHandler;
use Baza\Shared\Domain\Bus\Command\CommandBus;
use Baza\Shared\Infrastructure\Bus\Command\InMemorySymfonyCommandBus;
use Throwable;

class OpenAndCloseChanges
{
    private CommandBus $bus;

    public function __construct(
        OpenChangeCommandHandler $openChangeCommandHandler,
        CloseChangeCommandHandler $closeChangeCommandHandler,
    )
    {
        $this->bus= new InMemorySymfonyCommandBus([
            OpenChangeCommand::class => $openChangeCommandHandler,
            CloseChangeCommand::class => $closeChangeCommandHandler,
        ]);
    }

    /**
     * @throws Throwable
     */
    public function open(int $userId): void
    {
        $this->bus->dispatch(new OpenChangeCommand($userId));
    }

    /**
     * @throws Throwable
     */
    public function close(int $userId): void
    {
        $this->bus->dispatch(new CloseChangeCommand($userId));
    }
}
