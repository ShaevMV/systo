<?php

declare(strict_types=1);

namespace Tickets\Ticket\CreateTickets\Application;

use Illuminate\Support\Facades\Bus;
use Throwable;
use Tickets\Order\Shared\Dto\GuestsDto;
use Tickets\Shared\Domain\ValueObject\Uuid;
use Tickets\Shared\Infrastructure\Bus\Command\InMemorySymfonyCommandBus;
use Tickets\Shared\Infrastructure\Bus\Query\InMemorySymfonyQueryBus;
use Tickets\Ticket\CreateTickets\Application\Cancel\CancelTicketCommand;
use Tickets\Ticket\CreateTickets\Application\Cancel\CancelTicketCommandHandler;
use Tickets\Ticket\CreateTickets\Application\Create\CreateTicketCommand;
use Tickets\Ticket\CreateTickets\Application\Create\CreateTicketCommandHandler;
use Tickets\Ticket\CreateTickets\Application\CreateForFriendly\CreateTicketFriendlyCommand;
use Tickets\Ticket\CreateTickets\Application\CreateForFriendly\CreateTicketFriendlyCommandHandler;
use Tickets\Ticket\CreateTickets\Application\GetPdf\GetPdfQuery;
use Tickets\Ticket\CreateTickets\Application\GetPdf\GetPdfQueryHandler;
use Tickets\Ticket\CreateTickets\Application\GetTicket\GetTicketHandler;
use Tickets\Ticket\CreateTickets\Application\GetTicket\GetTicketQuery;
use Tickets\Ticket\CreateTickets\Application\GetTicket\TicketResponse;
use Tickets\Ticket\CreateTickets\Domain\Ticket;
use Tickets\Ticket\CreateTickets\Dto\TicketDto;
use Tickets\Ticket\CreateTickets\Responses\UrlsTicketPdfResponse;
use Tickets\Ticket\CreateTickets\Services\Dto\DataInfoForPdf;

class TicketApplication
{
    private InMemorySymfonyCommandBus $commandBus;
    private InMemorySymfonyQueryBus $queryBus;

    public function __construct(
        CreateTicketCommandHandler $commandHandler,
        CreateTicketFriendlyCommandHandler $createTicketFriendlyCommandHandler,
        CancelTicketCommandHandler $cancelTicketCommandHandler,
        GetPdfQueryHandler $pdfQueryHandler,
        GetTicketHandler $getTicketHandler,
        private Bus $bus
    ) {
        $this->commandBus = new InMemorySymfonyCommandBus([
            CreateTicketCommand::class => $commandHandler,
            CreateTicketFriendlyCommand::class => $createTicketFriendlyCommandHandler,
            CancelTicketCommand::class => $cancelTicketCommandHandler,

        ]);

        $this->queryBus = new InMemorySymfonyQueryBus([
            GetPdfQuery::class => $pdfQueryHandler,
            GetTicketQuery::class => $getTicketHandler,
        ]);
    }

    /**
     * @param  GuestsDto[]  $guests
     *
     * @return Ticket[]
     * @throws Throwable
     */
    public function createList(Uuid $orderId, array $guests): array
    {
        $tickets = [];
        foreach ($guests as $guest) {
            $ticketDto =  new TicketDto(
                $orderId,
                $guest->getValue(),
                $guest->getId() ?? null,
            );
            $this->commandBus->dispatch(new CreateTicketCommand(
                $ticketDto
            ));
            /** @var TicketResponse $ticketResponse */
            $ticketResponse = $this->queryBus->ask(new GetTicketQuery($ticketDto->getId()));

            $ticket = Ticket::newTicket($ticketResponse);

            $this->bus::chain($ticket->pullDomainEvents())
                ->dispatch();

            $tickets[] = $ticket;
        }

        return $tickets;
    }

    /**
     * @param  GuestsDto[]  $guests
     *
     * @return Ticket[]
     * @throws Throwable
     */
    public function createListForFriendly(Uuid $orderId, array $guests): array
    {
        $tickets = [];
        foreach ($guests as $guest) {
            $ticketDto =  new TicketDto(
                $orderId,
                $guest->getValue(),
                $guest->getId() ?? null,
            );
            $this->commandBus->dispatch(new CreateTicketFriendlyCommand(
                $ticketDto
            ));
            /** @var TicketResponse $ticketResponse */
            $ticketResponse = $this->queryBus->ask(new GetTicketQuery($ticketDto->getId()));

            $ticket = Ticket::newTicket($ticketResponse);

            $this->bus::chain($ticket->pullDomainEvents())
                ->dispatch();

            $tickets[] = $ticket;
        }

        return $tickets;
    }

    /**
     * @throws Throwable
     */
    public function cancelTicket(Uuid $orderId): void
    {
        $this->commandBus->dispatch(new CancelTicketCommand($orderId));
    }

    public function getPdfList(Uuid $orderId): UrlsTicketPdfResponse
    {
        /** @var UrlsTicketPdfResponse $result */
        $result = $this->queryBus->ask(new GetPdfQuery($orderId));

        return $result;
    }
}
