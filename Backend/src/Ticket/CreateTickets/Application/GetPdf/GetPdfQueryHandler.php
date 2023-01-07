<?php

declare(strict_types=1);

namespace Tickets\Ticket\CreateTickets\Application\GetPdf;

use Tickets\Shared\Domain\Bus\Query\QueryHandler;
use Tickets\Ticket\CreateTickets\Repositories\TicketsRepositoryInterface;
use Tickets\Ticket\CreateTickets\Responses\UrlsTicketPdfResponse;

class GetPdfQueryHandler implements QueryHandler
{
    private const PATH = 'storage/tickets/';

    public function __construct(
        private TicketsRepositoryInterface $ticketsRepository
    ) {
    }

    public function __invoke(GetPdfQuery $query): UrlsTicketPdfResponse
    {
        $listIds = $this->ticketsRepository->getListIdByOrderId($query->getOrderId());
        $listUrl = [];
        foreach ($listIds as $id) {
            $listUrl[] = asset(self::PATH.$id->value().'.pdf');
        }

        return new UrlsTicketPdfResponse($listUrl);
    }
}
