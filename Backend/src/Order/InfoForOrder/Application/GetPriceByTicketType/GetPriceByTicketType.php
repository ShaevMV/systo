<?php

declare(strict_types = 1);

namespace Tickets\Order\InfoForOrder\Application\GetPriceByTicketType;

use Tickets\Order\InfoForOrder\Application\GetInfoForOrder\GetAllInfoForOrderQuery;
use Tickets\Order\InfoForOrder\Response\PriceByTicketTypeResponse;
use Tickets\Shared\Domain\ValueObject\Uuid;
use Tickets\Shared\Infrastructure\Bus\Query\InMemorySymfonyQueryBus;

final class GetPriceByTicketType
{
    private InMemorySymfonyQueryBus $queryBus;

    public function __construct(
        GetPriceByTicketTypeQueryHandler $handler
    ){
        $this->queryBus = new InMemorySymfonyQueryBus([
            GetAllInfoForOrderQuery::class => $handler
        ]);
    }

    public function getPrice(Uuid $ticketsTypeId): PriceByTicketTypeResponse
    {
        /** @var PriceByTicketTypeResponse $result */
        $result = $this->queryBus->ask(new GetPriceByTicketTypeQuery($ticketsTypeId));

        return $result;
    }
}
