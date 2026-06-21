<?php

declare(strict_types=1);

namespace Baza\ShiftSchedule\Applications\CreateSchedule;

use Baza\Shared\Domain\Bus\Command\CommandHandler;
use Baza\ShiftSchedule\Repositories\ShiftScheduleRepositoryInterface;
use DomainException;

class CreateScheduleCommandHandler implements CommandHandler
{
    public function __construct(
        private ShiftScheduleRepositoryInterface $repository,
    ) {
    }

    public function __invoke(CreateScheduleCommand $command): void
    {
        $id = $this->repository->create($command->getDto());

        if ($id <= 0) {
            throw new DomainException('Не получилось создать плановую смену');
        }
    }
}
