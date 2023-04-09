<?php

declare(strict_types=1);

namespace Tickets\Ticket\CreateTickets\Application\PushTicket;

use Tickets\Shared\Domain\Bus\Query\QueryBus;
use Tickets\Shared\Domain\ValueObject\Uuid;
use Tickets\Shared\Infrastructure\Bus\Query\InMemorySymfonyQueryBus;
use GuzzleHttp\Client;

class PushTicket
{
    private QueryBus $bus;

    public function __construct(
        private PushTicketQueryHandler $pushTicketQueryHandler
    )
    {
        $this->bus = new InMemorySymfonyQueryBus([
            PushTicketQuery::class => $this->pushTicketQueryHandler
        ]);
    }


    public function pushTicket(?Uuid $id = null): void
    {
        /** @var PushTicketsResponse $pushTicketsResponse */
        $pushTicketsResponse=$this->bus->ask(new PushTicketQuery($id));

        $client = new Client([
            'headers' => [
                'content-type' => 'application/json',
                'Accept' => 'application/json'
            ],
        ]);
    }
}
