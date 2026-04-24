<?php

declare(strict_types=1);

namespace Tickets\Location\Application\Delete;

use Shared\Domain\Bus\Command\CommandHandler;
use Tickets\Location\Repository\LocationRepositoryInterface;

class LocationDeleteCommandHandler implements CommandHandler
{
    public function __construct(
        private LocationRepositoryInterface $repository,
    ) {
    }

    public function __invoke(LocationDeleteCommand $command): void
    {
        $this->repository->remove($command->getId());
    }
}
