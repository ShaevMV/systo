<?php

declare(strict_types=1);

namespace Tickets\Ticket\CreateTickets\Application\Cancel;

use Shared\Domain\Bus\Command\CommandHandler;
use Tickets\Ticket\CreateTickets\Repositories\TicketsRepositoryInterface;

class CancelTicketCommandHandler implements CommandHandler
{
    public function __construct(
        private TicketsRepositoryInterface $ticketsRepository
    ) {
    }

    public function __invoke(CancelTicketCommand $command)
    {
        $this->ticketsRepository->deleteTicketsByOrderId($command->getOrderId());
    }
}
