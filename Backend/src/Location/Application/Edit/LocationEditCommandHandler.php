<?php

declare(strict_types=1);

namespace Tickets\Location\Application\Edit;

use Shared\Domain\Bus\Command\CommandHandler;
use Tickets\Location\Repositories\LocationRepositoryInterface;

class LocationEditCommandHandler implements CommandHandler
{
    public function __construct(
        private LocationRepositoryInterface $repository
    ) {
    }

    public function __invoke(LocationEditCommand $command): void
    {
        $this->repository->editItem($command->getId(), $command->getData());
    }
}
