<?php

declare(strict_types=1);

namespace Baza\Tickets\Applications\Search;

use Baza\Tickets\Repositories\AutoTicketRepositoryInterface;
use Baza\Tickets\Repositories\ElTicketsRepositoryInterface;
use Baza\Tickets\Repositories\FriendlyTicketRepositoryInterface;
use Baza\Tickets\Repositories\LiveTicketRepositoryInterface;
use Baza\Tickets\Repositories\SpisokTicketsRepositoryInterface;
use Baza\Tickets\Repositories\TicketSearchRepositoryInterface;
use Baza\Tickets\Responses\SearchResponse;

class SearchService
{
    public function __construct(
        private SpisokTicketsRepositoryInterface $spisokTicketsRepository,
        private ElTicketsRepositoryInterface $elTicketsRepository,
        private FriendlyTicketRepositoryInterface $friendlyTicketRepository,
        private LiveTicketRepositoryInterface $liveTicketRepository,
        private AutoTicketRepositoryInterface $autoTicketRepository,
        private TicketSearchRepositoryInterface $ticketSearchRepository,
    ) {}

    public function find(string $q): SearchResponse
    {
        return new SearchResponse(
            $this->spisokTicketsRepository->find($q),
            $this->elTicketsRepository->find($q),
            $this->friendlyTicketRepository->find($q),
            $this->liveTicketRepository->find($q),
            $this->autoTicketRepository->find($q),
            // Богатый поиск по всем полям (ticket_search) — для гостей без QR.
            $this->ticketSearchRepository->find($q),
        );
    }
}
