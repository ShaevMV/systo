<?php

declare(strict_types=1);

namespace Tickets\TicketType\Application\Edit;

use Shared\Domain\Bus\Command\CommandHandler;
use Tickets\TypesOfPayment\Repositories\TypesOfPaymentRepositoryInterface;

class TicketTypeEditCommandHandler implements CommandHandler
{
    public function __construct(
        private TypesOfPaymentRepositoryInterface $repository
    )
    {
    }

    public function __invoke(TicketTypeEditCommand $command): void
    {
        $this->repository->editItem(
            $command->getId(),
            $command->getPaymentDto(),
        );
    }
}
