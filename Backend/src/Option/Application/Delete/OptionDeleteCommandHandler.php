<?php

declare(strict_types=1);

namespace Tickets\Option\Application\Delete;

use Shared\Domain\Bus\Command\CommandHandler;
use Tickets\Option\Repositories\OptionRepositoryInterface;

class OptionDeleteCommandHandler implements CommandHandler
{
    public function __construct(
        private OptionRepositoryInterface $repository
    ) {
    }

    public function __invoke(OptionDeleteCommand $command): void
    {
        $this->repository->remove($command->getId());
    }
}
