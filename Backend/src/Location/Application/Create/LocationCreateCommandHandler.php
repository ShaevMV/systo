<?php

declare(strict_types=1);

namespace Tickets\Location\Application\Create;

use Shared\Domain\Bus\Command\CommandHandler;
use Tickets\Location\Domain\Location;
use Tickets\Location\Repository\LocationRepositoryInterface;

class LocationCreateCommandHandler implements CommandHandler
{
    public function __construct(
        private LocationRepositoryInterface $repository,
    ) {
    }

    public function __invoke(LocationCreateCommand $command): void
    {
        // Создаём доменный объект — он гарантирует корректность данных
        Location::create($command->getData());
        $this->repository->create($command->getData());
    }
}
