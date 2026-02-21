<?php

declare(strict_types=1);

namespace Tickets\TicketType\Application\Delete;

use Shared\Domain\Bus\Command\CommandHandler;
use Tickets\TypesOfPayment\Repositories\TypesOfPaymentRepositoryInterface;

class TicketTypeDeleteCommandHandler implements CommandHandler
{
    public function __construct(
        private TypesOfPaymentRepositoryInterface $repository
    )
    {
    }

    public function __invoke(TicketTypeDeleteCommand $command): void
    {
        $this->repository->remove($command->getId());
    }
}
