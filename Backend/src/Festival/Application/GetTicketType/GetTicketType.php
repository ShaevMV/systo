<?php

declare(strict_types = 1);

namespace Tickets\Festival\Application\GetTicketType;

use Carbon\Carbon;
use Shared\Domain\ValueObject\Uuid;
use Shared\Infrastructure\Bus\Query\InMemorySymfonyQueryBus;
use Tickets\Festival\Response\PriceByTicketTypeResponse;
use Tickets\Festival\Response\TicketTypeDto;

final class GetTicketType
{
    private InMemorySymfonyQueryBus $queryBus;

    public function __construct(
        GetPriceByTicketTypeQueryHandler $priceByTicketTypeQueryHandler,
        GetTicketTypeQueryHandler $getTicketTypeQueryHandler,
    ){
        $this->queryBus = new InMemorySymfonyQueryBus([
            GetPriceByTicketTypeQuery::class => $priceByTicketTypeQueryHandler,
            GetTicketTypeQuery::class => $getTicketTypeQueryHandler,
        ]);
    }

    public function getPrice(Uuid $ticketsTypeId, Carbon $dateTime): PriceByTicketTypeResponse
    {
        /** @var PriceByTicketTypeResponse $result */
        $result = $this->queryBus->ask(new GetPriceByTicketTypeQuery(
            $ticketsTypeId,
            $dateTime
        ));

        return $result;
    }

    public function isGroupTicket(Uuid $ticketsTypeId): bool
    {
        /** @var TicketTypeDto $result */
        $result = $this->queryBus->ask(new GetTicketTypeQuery($ticketsTypeId));

        return $result->getGroupLimit() !== null;
    }

    public function getTicketsTypeByUuid(Uuid $ticketsTypeId): TicketTypeDto
    {
        /** @var TicketTypeDto $result */
        $result = $this->queryBus->ask(new GetTicketTypeQuery($ticketsTypeId));

        return $result;
    }
}
