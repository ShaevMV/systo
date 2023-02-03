<?php

namespace Tickets\Order\InfoForOrder\Application\GetTicketType;

use Tickets\Order\InfoForOrder\Repositories\TicketTypeInterface;
use Tickets\Order\InfoForOrder\Response\TicketTypeDto;
use Tickets\Shared\Domain\Bus\Query\QueryHandler;

class GetTicketTypeQueryHandler implements QueryHandler
{
    public function __construct(
        private TicketTypeInterface $ticketType,
    ) {
    }

    public function __invoke(GetTicketTypeQuery $query): TicketTypeDto
    {
        return $this->ticketType->getById($query->getUuid());
    }
}
