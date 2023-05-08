<?php

declare(strict_types=1);

namespace Baza\Changes\Applications\SaveChange;

use Baza\Shared\Domain\Bus\Command\CommandBus;
use Baza\Shared\Infrastructure\Bus\Command\InMemorySymfonyCommandBus;
use Carbon\Carbon;
use Throwable;

class SaveChange
{
    private CommandBus $bus;

    public function __construct(
        SaveChangeCommandHandler $saveChangeCommandHandler
    )
    {
        $this->bus = new InMemorySymfonyCommandBus([
            SaveChangeCommand::class => $saveChangeCommandHandler
        ]);
    }

    /**
     * @throws Throwable
     */
    public function save(array $userIdList, Carbon $start, ?int $id=null): void
    {
        $this->bus->dispatch(new SaveChangeCommand($userIdList, $start, $id));
    }
}
