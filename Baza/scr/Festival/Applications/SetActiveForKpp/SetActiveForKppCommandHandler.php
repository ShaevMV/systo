<?php

declare(strict_types=1);

namespace Baza\Festival\Applications\SetActiveForKpp;

use Baza\Festival\Repositories\FestivalRepositoryInterface;
use Baza\Shared\Domain\Bus\Command\CommandHandler;
use DomainException;

class SetActiveForKppCommandHandler implements CommandHandler
{
    public function __construct(
        private FestivalRepositoryInterface $repository,
    ) {
    }

    public function __invoke(SetActiveForKppCommand $command): void
    {
        if (! $this->repository->setActiveForKpp($command->getFestivalId(), $command->isActive())) {
            throw new DomainException('Фестиваль не найден');
        }
    }
}
