<?php

declare(strict_types=1);

namespace Baza\ShiftSchedule\Applications\CancelSchedule;

use Baza\Shared\Domain\Bus\Command\CommandHandler;
use Baza\ShiftSchedule\Repositories\ShiftScheduleRepositoryInterface;
use DomainException;

class CancelScheduleCommandHandler implements CommandHandler
{
    public function __construct(
        private ShiftScheduleRepositoryInterface $repository,
    ) {
    }

    public function __invoke(CancelScheduleCommand $command): void
    {
        if (! $this->repository->cancel($command->getId())) {
            throw new DomainException('Не получилось отменить плановую смену');
        }
    }
}
