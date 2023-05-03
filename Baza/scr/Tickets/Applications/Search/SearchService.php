<?php

declare(strict_types=1);

namespace Baza\Tickets\Applications\Search;

use Baza\Tickets\Repositories\ElTicketsRepositoryInterface;
use Baza\Tickets\Repositories\FriendlyTicketRepositoryInterface;
use Baza\Tickets\Repositories\SpisokTicketsRepositoryInterface;
use Baza\Tickets\Responses\SearchResponse;

class SearchService
{
    public function __construct(
        private SpisokTicketsRepositoryInterface  $spisokTicketsRepository,
        private ElTicketsRepositoryInterface      $elTicketsRepository,
        private FriendlyTicketRepositoryInterface $friendlyTicketRepository,
    )
    {
    }

    public function find(string $q): SearchResponse
    {
        return new SearchResponse(
            $this->spisokTicketsRepository->find($q),
            $this->elTicketsRepository->find($q),
            $this->friendlyTicketRepository->find($q)
        );
    }
}
