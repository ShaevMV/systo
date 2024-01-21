<?php

declare(strict_types=1);

namespace Tickets\Order\InfoForOrder\Application\GetPriceList;

use Tickets\Order\InfoForOrder\Application\GetInfoForOrder\ListTicketTypeQuery;
use Tickets\Order\InfoForOrder\Application\GetInfoForOrder\ListTicketTypeQueryHandler;
use Tickets\Order\InfoForOrder\Response\ListTicketTypeDto;
use Shared\Domain\ValueObject\Uuid;
use Shared\Infrastructure\Bus\Query\InMemorySymfonyQueryBus;

class GetPriceList
{
    private InMemorySymfonyQueryBus $queryBus;

    public function __construct(ListTicketTypeQueryHandler $getAllInfoForOrderQueryHandler)
    {
        $this->queryBus = new InMemorySymfonyQueryBus([
            ListTicketTypeQuery::class => $getAllInfoForOrderQueryHandler
        ]);
    }

    public function getAllPrice(Uuid $festivalId): ListTicketTypeDto
    {
        /** @var ListTicketTypeDto $result */
        $result = $this->queryBus->ask(new GetPriceListQuery($festivalId));

        return $result;
    }
}
