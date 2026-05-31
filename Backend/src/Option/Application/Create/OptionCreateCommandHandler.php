<?php

declare(strict_types=1);

namespace Tickets\Option\Application\Create;

use Shared\Domain\Bus\Command\CommandHandler;
use Tickets\Option\Repositories\OptionRepositoryInterface;

class OptionCreateCommandHandler implements CommandHandler
{
    public function __construct(
        private OptionRepositoryInterface $repository
    ) {
    }

    public function __invoke(OptionCreateCommand $command): void
    {
        $this->repository->create($command->getData());

        if (! empty($command->getBindings())) {
            $this->repository->syncTicketTypes(
                $command->getData()->getId(),
                $command->getBindings()
            );
        }
    }
}
