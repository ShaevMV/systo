<?php

declare(strict_types=1);

namespace Tickets\Festival\Application\GetTicketType;



use Tickets\Festival\Repositories\TicketTypeInterfaceRepository;
use Tickets\Festival\Response\PriceByTicketTypeResponse;

class GetPriceByTicketTypeQueryHandler
{
    public function __construct(
        private TicketTypeInterfaceRepository $ticketType
    ) {
    }

    public function __invoke(GetPriceByTicketTypeQuery $query): PriceByTicketTypeResponse
    {
        $ticketType = $this->ticketType->getById($query->getUuid(), $query->getCarbon());

        return new PriceByTicketTypeResponse(
            $ticketType->getPrice(),
            $ticketType->getGroupLimit() > 0
        );
    }
}
