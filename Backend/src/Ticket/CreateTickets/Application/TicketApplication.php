<?php

declare(strict_types=1);

namespace Tickets\Ticket\CreateTickets\Application;

use Illuminate\Support\Facades\Bus;
use Throwable;
use Tickets\Order\OrderTicket\Dto\OrderTicket\GuestsDto;
use Tickets\Shared\Domain\ValueObject\Uuid;
use Tickets\Shared\Infrastructure\Bus\Command\InMemorySymfonyCommandBus;
use Tickets\Shared\Infrastructure\Bus\Query\InMemorySymfonyQueryBus;
use Tickets\Ticket\CreateTickets\Application\Cancel\CancelTicketCommand;
use Tickets\Ticket\CreateTickets\Application\Cancel\CancelTicketCommandHandler;
use Tickets\Ticket\CreateTickets\Application\Create\CreateTicketCommand;
use Tickets\Ticket\CreateTickets\Application\Create\CreateTicketCommandHandler;
use Tickets\Ticket\CreateTickets\Application\GetPdf\GetPdfQuery;
use Tickets\Ticket\CreateTickets\Application\GetPdf\GetPdfQueryHandler;
use Tickets\Ticket\CreateTickets\Domain\Ticket;
use Tickets\Ticket\CreateTickets\Dto\TicketDto;
use Tickets\Ticket\CreateTickets\Responses\UrlsTicketPdfResponse;

class TicketApplication
{
    private InMemorySymfonyCommandBus $commandBus;
    private InMemorySymfonyQueryBus $queryBus;


    public function __construct(
        CreateTicketCommandHandler $commandHandler,
        CancelTicketCommandHandler $cancelTicketCommandHandler,
        GetPdfQueryHandler $pdfQueryHandler,
        private Bus $bus
    ) {
        $this->commandBus = new InMemorySymfonyCommandBus([
            CreateTicketCommand::class => $commandHandler,
            CancelTicketCommand::class => $cancelTicketCommandHandler,
        ]);

        $this->queryBus = new InMemorySymfonyQueryBus([
            GetPdfQuery::class => $pdfQueryHandler,
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
            $ticket = Ticket::newTicket($orderId, $guest->getValue());

            $this->commandBus->dispatch(new CreateTicketCommand(
                new TicketDto(
                    $orderId,
                    $guest->getValue(),
                    $ticket->getAggregateId()
                )
            ));

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
