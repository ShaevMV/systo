<?php

namespace Baza\Changes\Applications\OpenAndClose\Open;

use Baza\Changes\Repositories\ChangesRepositoryInterface;
use Baza\Shared\Domain\Bus\Command\CommandHandler;
use DomainException;

class OpenChangeCommandHandler implements CommandHandler
{
    public function __construct(
        private ChangesRepositoryInterface $repository
    )
    {
    }

    public function __invoke(OpenChangeCommand $command): void
    {
        if ($this->repository->open($command->getUserId()) > 0) {
            return;
        }

        throw new DomainException('Смена не открыта');
    }
}
