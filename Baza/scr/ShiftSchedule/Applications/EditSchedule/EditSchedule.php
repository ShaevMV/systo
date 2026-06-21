<?php

declare(strict_types=1);

namespace Baza\ShiftSchedule\Applications\EditSchedule;

use Baza\Shared\Domain\Bus\Command\CommandBus;
use Baza\Shared\Infrastructure\Bus\Command\InMemorySymfonyCommandBus;
use Baza\ShiftSchedule\Dto\ShiftScheduleDto;
use Throwable;

/**
 * Изменение плановой смены (PR-A). Тонкий слой — БД в репозитории.
 */
class EditSchedule
{
    private CommandBus $bus;

    public function __construct(
        EditScheduleCommandHandler $editScheduleCommandHandler,
    ) {
        $this->bus = new InMemorySymfonyCommandBus([
            EditScheduleCommand::class => $editScheduleCommandHandler,
        ]);
    }

    /**
     * @throws Throwable
     */
    public function edit(int $id, ShiftScheduleDto $dto): void
    {
        $this->bus->dispatch(new EditScheduleCommand($id, $dto));
    }
}
