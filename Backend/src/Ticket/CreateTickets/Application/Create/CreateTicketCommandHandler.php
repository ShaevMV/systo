<?php

namespace Tickets\Ticket\CreateTickets\Application\Create;

use Tickets\Shared\Domain\Bus\Command\CommandHandler;
use Tickets\Ticket\CreateTickets\Repositories\TicketsRepositoryInterface;

class CreateTicketCommandHandler implements CommandHandler
{
    public function __construct(
        private TicketsRepositoryInterface $ticketsRepository
    ){
    }


    public function __invoke(CreateTicketCommand $ticket): void
    {
        $this->ticketsRepository->createTickets($ticket->getTicketDto());
    }
}
