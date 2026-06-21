<?php

declare(strict_types=1);

namespace Baza\ShiftSchedule\Applications\CancelSchedule;

use Baza\Shared\Domain\Bus\Command\CommandBus;
use Baza\Shared\Infrastructure\Bus\Command\InMemorySymfonyCommandBus;
use Throwable;

/**
 * Отмена плановой смены (PR-A, status = cancelled). Тонкий слой — БД в репозитории.
 */
class CancelSchedule
{
    private CommandBus $bus;

    public function __construct(
        CancelScheduleCommandHandler $cancelScheduleCommandHandler,
    ) {
        $this->bus = new InMemorySymfonyCommandBus([
            CancelScheduleCommand::class => $cancelScheduleCommandHandler,
        ]);
    }

    /**
     * @throws Throwable
     */
    public function cancel(int $id): void
    {
        $this->bus->dispatch(new CancelScheduleCommand($id));
    }
}
