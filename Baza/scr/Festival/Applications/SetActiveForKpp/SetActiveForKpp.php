<?php

declare(strict_types=1);

namespace Baza\Festival\Applications\SetActiveForKpp;

use Baza\Shared\Domain\Bus\Command\CommandBus;
use Baza\Shared\Infrastructure\Bus\Command\InMemorySymfonyCommandBus;
use Throwable;

/**
 * Тумблер «фестиваль доступен для КПП» (TD-48, PR-1). Тонкий слой — БД в репозитории.
 * Стиль по образцу Baza\ShiftSchedule\Applications\CreateSchedule\CreateSchedule.
 */
class SetActiveForKpp
{
    private CommandBus $bus;

    public function __construct(
        SetActiveForKppCommandHandler $setActiveForKppCommandHandler,
    ) {
        $this->bus = new InMemorySymfonyCommandBus([
            SetActiveForKppCommand::class => $setActiveForKppCommandHandler,
        ]);
    }

    /**
     * @throws Throwable
     */
    public function set(string $festivalId, bool $active): void
    {
        $this->bus->dispatch(new SetActiveForKppCommand($festivalId, $active));
    }
}
