<?php

declare(strict_types=1);

namespace Tickets\Ordering\InfoForOrder\Application\GetPriceByTicketType;

use Tickets\Ordering\InfoForOrder\Application\GetInfoForOrder\GetAllInfoForOrderQuery;
use Tickets\Ordering\InfoForOrder\Repositories\TicketTypeInterface;

class GetPriceByTicketTypeQueryHandler
{
    public function __construct(
        private TicketTypeInterface $ticketType
    ) {
    }

    public function __invoke(GetPriceByTicketTypeQuery $query): PriceByTicketTypeResponse
    {
        return new PriceByTicketTypeResponse($this->ticketType->getById($query->getUuid())->getPrice());
    }
}
