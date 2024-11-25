<?php

namespace Baza\Changes\Applications\OpenAndClose\Close;

use Baza\Changes\Applications\OpenAndClose\Open\OpenChangeCommand;
use Baza\Changes\Repositories\ChangesRepositoryInterface;
use Baza\Shared\Domain\Bus\Command\CommandHandler;
use DomainException;

class CloseChangeCommandHandler implements CommandHandler
{
    public function __construct(
        private ChangesRepositoryInterface $repository
    )
    {
    }

    public function __invoke(CloseChangeCommand $command): void
    {
        if ($this->repository->close($command->getChangeId()) > 0) {
            return;
        }

        throw new DomainException('Смена не закрыта');
    }
}
