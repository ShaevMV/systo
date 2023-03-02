<?php

declare(strict_types=1);

namespace Tickets\Order\InfoForOrder\Application\GetPriceList;

use Tickets\Order\InfoForOrder\Response\ListTicketTypeDto;
use Tickets\Shared\Infrastructure\Bus\Query\InMemorySymfonyQueryBus;

class GetPriceList
{
    private InMemorySymfonyQueryBus $queryBus;

    public function __construct(GetPriceListQueryHandler $getPriceLIstQueryHandler)
    {
        $this->queryBus = new InMemorySymfonyQueryBus([
            GetPriceListQuery::class => $getPriceLIstQueryHandler
        ]);
    }

    public function getAllPrice(): ListTicketTypeDto
    {
        /** @var ListTicketTypeDto $result */
        $result = $this->queryBus->ask(new GetPriceListQuery());

        return $result;
    }
}
