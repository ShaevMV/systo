<?php

declare(strict_types=1);

namespace Tickets\TicketTypePrice\Application\Create;

use Shared\Domain\Bus\Command\CommandHandler;
use Tickets\TicketTypePrice\Repositories\TicketTypePriceRepositoryInterface;

class TicketTypePriceCreateCommandHandler implements CommandHandler
{
    public function __construct(
        private TicketTypePriceRepositoryInterface $repository
    ) {
    }

    public function __invoke(TicketTypePriceCreateCommand $command): void
    {
        $this->repository->create($command->getData());
    }
}
