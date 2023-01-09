<?php

declare(strict_types=1);

namespace Tickets\Order\InfoForOrder\Application\GetPriceByTicketType;

use Tickets\Order\InfoForOrder\Repositories\TicketTypeInterface;
use Tickets\Order\InfoForOrder\Response\PriceByTicketTypeResponse;

class GetPriceByTicketTypeQueryHandler
{
    public function __construct(
        private TicketTypeInterface $ticketType
    ) {
    }

    public function __invoke(GetPriceByTicketTypeQuery $query): PriceByTicketTypeResponse
    {
        $ticketType = $this->ticketType->getById($query->getUuid());

        return new PriceByTicketTypeResponse(
            $ticketType->getPrice(),
            $ticketType->getGroupLimit() > 0
        );
    }
}
