<?php

namespace Baza\Changes\Applications\SaveChange;

use Baza\Changes\Repositories\ChangesRepositoryInterface;
use Baza\Shared\Domain\Bus\Command\CommandHandler;

class SaveChangeCommandHandler implements CommandHandler
{
    public function __construct(
        private ChangesRepositoryInterface $changesRepository
    )
    {
    }

    public function __invoke(SaveChangeCommand $command)
    {
        if(!$this->changesRepository->updateOrCreate($command->getUserIdList(), $command->getStart(), $command->getId())){
            throw new \DomainException('Не получилось сохранить смену');
        }
    }
}
