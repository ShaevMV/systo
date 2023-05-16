<?php

declare(strict_types=1);

namespace Baza\Changes\Applications\AddTicketsInReport;

use Baza\Changes\Repositories\ChangesRepositoryInterface;
use Baza\Shared\Domain\Bus\Command\CommandHandler;
use DomainException;
use InvalidArgumentException;
use Baza\Shared\Services\DefineService;

class AddTicketsInReportCommandHandler implements CommandHandler
{
    public function __construct(
        private ChangesRepositoryInterface $repository
    )
    {
    }

    public function __invoke(AddTicketsInReportCommand $command): void
    {
        if (!isset(DefineService::TYPE_BY_COLONS_IN_CHANGES[$command->getTypeTicket()])) {
            throw new InvalidArgumentException('Не правильный тип билета ' . $command->getTypeTicket());
        }
        $columName = DefineService::TYPE_BY_COLONS_IN_CHANGES[$command->getTypeTicket()];

        if (!$this->repository->addTicket($columName, $command->getChangeId())) {
            throw new DomainException('Не получилось сохранить изменения в смене');
        }
    }
}
