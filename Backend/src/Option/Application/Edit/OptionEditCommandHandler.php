<?php

declare(strict_types=1);

namespace Tickets\Option\Application\Edit;

use Shared\Domain\Bus\Command\CommandHandler;
use Tickets\Option\Repositories\OptionRepositoryInterface;

class OptionEditCommandHandler implements CommandHandler
{
    public function __construct(
        private OptionRepositoryInterface $repository
    ) {
    }

    public function __invoke(OptionEditCommand $command): void
    {
        $this->repository->editItem($command->getId(), $command->getData());

        if ($command->getBindings() !== null) {
            $this->repository->syncTicketTypes(
                $command->getId(),
                $command->getBindings()
            );
        }
    }
}
