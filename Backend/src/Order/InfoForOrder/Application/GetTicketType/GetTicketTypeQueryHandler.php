<?php

namespace Tickets\Order\InfoForOrder\Application\GetTicketType;

use Tickets\Order\InfoForOrder\Repositories\TicketTypeInterfaceRepository;
use Tickets\Order\InfoForOrder\Response\TicketTypeDto;
use Shared\Domain\Bus\Query\QueryHandler;

class GetTicketTypeQueryHandler implements QueryHandler
{
    public function __construct(
        private TicketTypeInterfaceRepository $ticketType,
    ) {
    }

    public function __invoke(GetTicketTypeQuery $query): TicketTypeDto
    {
        return $this->ticketType->getById($query->getUuid());
    }
}
