<?php

declare(strict_types=1);

namespace Tickets\TicketTypePrice\Application\Edit;

use Shared\Domain\Bus\Command\CommandHandler;
use Tickets\TicketTypePrice\Repositories\TicketTypePriceRepositoryInterface;

class TicketTypePriceEditCommandHandler implements CommandHandler
{
    public function __construct(
        private TicketTypePriceRepositoryInterface $repository
    ) {
    }

    public function __invoke(TicketTypePriceEditCommand $command): void
    {
        $this->repository->editItem($command->getId(), $command->getData());
    }
}
