<?php

declare(strict_types=1);

namespace Tickets\TicketType\Application\Create;

use Shared\Domain\Bus\Command\CommandHandler;
use Tickets\TypesOfPayment\Repositories\TypesOfPaymentRepositoryInterface;

class TicketTypeCreateCommandHandler implements CommandHandler
{
    public function __construct(
        private TypesOfPaymentRepositoryInterface $repository
    )
    {
    }

    public function __invoke(TicketTypeCreateCommand $command): void
    {
        $this->repository->create($command->getData());
    }
}
