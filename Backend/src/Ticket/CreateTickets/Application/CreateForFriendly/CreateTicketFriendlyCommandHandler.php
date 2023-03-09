<?php

namespace Tickets\Ticket\CreateTickets\Application\CreateForFriendly;

use Tickets\Shared\Domain\Bus\Command\CommandHandler;
use Tickets\Ticket\CreateTickets\Repositories\InMemoryMySqlTicketsFriendlyRepository;
use Tickets\Ticket\CreateTickets\Repositories\TicketsRepositoryInterface;

class CreateTicketFriendlyCommandHandler implements CommandHandler
{
    public function __construct(
        private InMemoryMySqlTicketsFriendlyRepository $ticketsRepository
    ){
    }


    /**
     * @throws \Throwable
     * @throws \JsonException
     */
    public function __invoke(CreateTicketFriendlyCommand $ticket)
    {
        $this->ticketsRepository->createTickets($ticket->getTicketDto());
    }
}
