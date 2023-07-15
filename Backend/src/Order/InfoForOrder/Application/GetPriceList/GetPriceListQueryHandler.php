<?php

namespace Tickets\Order\InfoForOrder\Application\GetPriceList;

use Tickets\Order\InfoForOrder\Repositories\TicketTypeInterfaceRepository;
use Tickets\Order\InfoForOrder\Response\ListTicketTypeDto;
use Shared\Domain\Bus\Query\QueryHandler;

class GetPriceListQueryHandler implements QueryHandler
{
    public function __construct(
        private TicketTypeInterfaceRepository $ticketType
    ){
    }

    public function __invoke(GetPriceListQuery $query): ListTicketTypeDto
    {
        return new ListTicketTypeDto($this->ticketType->getListPrice($query->getFestivalId()));
    }
}
