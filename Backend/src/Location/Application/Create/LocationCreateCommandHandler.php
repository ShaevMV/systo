<?php

declare(strict_types=1);

namespace Tickets\Location\Application\Create;

use Shared\Domain\Bus\Command\CommandHandler;
use Tickets\Location\Repositories\LocationRepositoryInterface;

class LocationCreateCommandHandler implements CommandHandler
{
    public function __construct(
        private LocationRepositoryInterface $repository
    ) {
    }

    public function __invoke(LocationCreateCommand $command): void
    {
        $this->repository->create($command->getData());
    }
}
