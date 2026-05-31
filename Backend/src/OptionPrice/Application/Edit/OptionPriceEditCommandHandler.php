<?php

declare(strict_types=1);

namespace Tickets\OptionPrice\Application\Edit;

use Shared\Domain\Bus\Command\CommandHandler;
use Tickets\OptionPrice\Repositories\OptionPriceRepositoryInterface;

class OptionPriceEditCommandHandler implements CommandHandler
{
    public function __construct(
        private OptionPriceRepositoryInterface $repository
    ) {
    }

    public function __invoke(OptionPriceEditCommand $command): void
    {
        $this->repository->editItem($command->getId(), $command->getData());
    }
}
