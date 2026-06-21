<?php

declare(strict_types=1);

namespace Baza\ShiftSchedule\Applications\EditSchedule;

use Baza\Shared\Domain\Bus\Command\CommandHandler;
use Baza\ShiftSchedule\Repositories\ShiftScheduleRepositoryInterface;
use DomainException;

class EditScheduleCommandHandler implements CommandHandler
{
    public function __construct(
        private ShiftScheduleRepositoryInterface $repository,
    ) {
    }

    public function __invoke(EditScheduleCommand $command): void
    {
        if (! $this->repository->edit($command->getId(), $command->getDto())) {
            throw new DomainException('Не получилось изменить плановую смену');
        }
    }
}
