<?php

declare(strict_types=1);

namespace Tickets\TicketTypePrice\Application\Delete;

use Shared\Domain\Bus\Command\CommandHandler;
use Tickets\TicketTypePrice\Repositories\TicketTypePriceRepositoryInterface;

class TicketTypePriceDeleteCommandHandler implements CommandHandler
{
    public function __construct(
        private TicketTypePriceRepositoryInterface $repository
    ) {
    }

    public function __invoke(TicketTypePriceDeleteCommand $command): void
    {
        $this->repository->remove($command->getId());
    }
}
