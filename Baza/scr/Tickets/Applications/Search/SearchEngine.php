<?php

declare(strict_types=1);

namespace Baza\Tickets\Applications\Search;

use Baza\Shared\Domain\Bus\Query\QueryBus;
use Baza\Shared\Infrastructure\Bus\Query\InMemorySymfonyQueryBus;
use Baza\Tickets\Applications\Search\ElTicket\ElTicketQuery;
use Baza\Tickets\Applications\Search\ElTicket\ElTicketsQueryHandler;
use Baza\Tickets\Applications\Search\SpisokTicket\SpisokTicketQuery;
use Baza\Tickets\Applications\Search\SpisokTicket\SpisokTicketQueryHandler;
use Baza\Tickets\Domain\Ticket;
use Tickets\Shared\Domain\ValueObject\Uuid;

class SearchEngine
{

    private QueryBus $bus;

    public function __construct(
        ElTicketsQueryHandler    $elSearchQueryHandler,
        SpisokTicketQueryHandler $spisokTicketQueryHandler,
    )
    {
        $this->bus = new InMemorySymfonyQueryBus([
            ElTicketQuery::class => $elSearchQueryHandler,
            SpisokTicketQuery::class => $spisokTicketQueryHandler,
        ]);
    }


    public function find(string $type, int|Uuid $identifier): Ticket
    {
        switch ()
    }
}
