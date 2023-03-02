<?php

namespace Tickets\Order\InfoForOrder\Application\GetPriceList;

use Tickets\Order\InfoForOrder\Repositories\TicketTypeInterface;
use Tickets\Order\InfoForOrder\Response\ListTicketTypeDto;
use Tickets\Shared\Domain\Bus\Query\QueryHandler;

class GetPriceListQueryHandler implements QueryHandler
{
    public function __construct(
        private TicketTypeInterface $ticketType
    ){
    }

    public function __invoke(GetPriceListQuery $query): ListTicketTypeDto
    {
        return new ListTicketTypeDto($this->ticketType->getListPrice());
    }
}
