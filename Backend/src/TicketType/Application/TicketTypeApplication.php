<?php

declare(strict_types=1);

namespace Tickets\TicketType\Application;

use Shared\Domain\Bus\Command\CommandBus;
use Shared\Domain\Bus\Query\QueryBus;
use Shared\Infrastructure\Bus\Command\InMemorySymfonyCommandBus;
use Shared\Infrastructure\Bus\Query\InMemorySymfonyQueryBus;
use Tickets\TicketType\Application\GetItem\TicketTypeGetListQuery;
use Tickets\TicketType\Application\GetItem\TicketTypeGetListQueryHandler;
use Tickets\TicketType\Response\TicketTypeGetListResponse;

class TicketTypeApplication
{
    private CommandBus $commandBus;
    private QueryBus $queryBus;

    public function __construct(TicketTypeGetListQueryHandler $ticketTypeGetListQueryHandler)
    {
        $this->commandBus = new InMemorySymfonyCommandBus([

        ]);

        $this->queryBus = new InMemorySymfonyQueryBus([
            TicketTypeGetListQuery::class => $ticketTypeGetListQueryHandler
        ]);
    }

    public function getList(TicketTypeGetListQuery $query): TicketTypeGetListResponse
    {
        /** @var TicketTypeGetListResponse $result */
        $result = $this->queryBus->ask($query);

        return $result;
    }
}
