<?php

declare(strict_types=1);

namespace Tickets\OptionPrice\Application\Create;

use Shared\Domain\Bus\Command\CommandHandler;
use Tickets\OptionPrice\Repositories\OptionPriceRepositoryInterface;

class OptionPriceCreateCommandHandler implements CommandHandler
{
    public function __construct(
        private OptionPriceRepositoryInterface $repository
    ) {
    }

    public function __invoke(OptionPriceCreateCommand $command): void
    {
        $this->repository->create($command->getData());
    }
}
