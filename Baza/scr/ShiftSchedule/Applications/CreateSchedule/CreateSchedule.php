<?php

declare(strict_types=1);

namespace Baza\ShiftSchedule\Applications\CreateSchedule;

use Baza\Shared\Domain\Bus\Command\CommandBus;
use Baza\Shared\Infrastructure\Bus\Command\InMemorySymfonyCommandBus;
use Baza\ShiftSchedule\Dto\ShiftScheduleDto;
use Throwable;

/**
 * Создание плановой смены (PR-A). Тонкий слой — БД в репозитории.
 * Стиль по образцу Baza\Changes\Applications\SaveChange\SaveChange.
 */
class CreateSchedule
{
    private CommandBus $bus;

    public function __construct(
        CreateScheduleCommandHandler $createScheduleCommandHandler,
    ) {
        $this->bus = new InMemorySymfonyCommandBus([
            CreateScheduleCommand::class => $createScheduleCommandHandler,
        ]);
    }

    /**
     * @throws Throwable
     */
    public function create(ShiftScheduleDto $dto): void
    {
        $this->bus->dispatch(new CreateScheduleCommand($dto));
    }
}
