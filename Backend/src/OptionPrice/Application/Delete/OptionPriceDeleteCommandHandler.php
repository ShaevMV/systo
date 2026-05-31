<?php

declare(strict_types=1);

namespace Tickets\OptionPrice\Application\Delete;

use Shared\Domain\Bus\Command\CommandHandler;
use Tickets\OptionPrice\Repositories\OptionPriceRepositoryInterface;

class OptionPriceDeleteCommandHandler implements CommandHandler
{
    public function __construct(
        private OptionPriceRepositoryInterface $repository
    ) {
    }

    public function __invoke(OptionPriceDeleteCommand $command): void
    {
        $this->repository->remove($command->getId());
    }
}
