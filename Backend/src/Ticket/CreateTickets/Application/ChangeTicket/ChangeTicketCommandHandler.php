<?php

declare(strict_types=1);

namespace Tickets\Ticket\CreateTickets\Application\ChangeTicket;

use Shared\Domain\Bus\Command\CommandHandler;
use Tickets\Ticket\CreateTickets\Repositories\TicketsRepositoryInterface;

class ChangeTicketCommandHandler implements CommandHandler
{
    public function __construct(
        private TicketsRepositoryInterface $ticketsRepository
    )
    {
    }

    public function __invoke(ChangeTicketCommand $command): void
    {

    }
}
