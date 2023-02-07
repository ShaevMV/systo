<?php

declare(strict_types=1);

namespace Tickets\Ticket\CreateTickets\Application\GetPdf;

use Tickets\Shared\Domain\Bus\Query\QueryHandler;
use Tickets\Ticket\CreateTickets\Repositories\TicketsRepositoryInterface;
use Tickets\Ticket\CreateTickets\Responses\UrlsTicketPdfResponse;

class GetPdfQueryHandler implements QueryHandler
{
    private const PATH = 'storage/tickets/';

    public function __invoke(GetPdfQuery $query): UrlsTicketPdfResponse
    {
        $listUrl[] = asset(self::PATH.$query->getTicketId()->value().'.pdf');

        return new UrlsTicketPdfResponse($listUrl);
    }
}
