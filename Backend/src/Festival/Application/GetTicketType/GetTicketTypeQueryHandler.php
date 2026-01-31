<?php

declare(strict_types=1);

namespace Tickets\Festival\Application\GetTicketType;

use Shared\Domain\Bus\Query\QueryHandler;
use Tickets\Festival\Repositories\TicketTypeInterfaceRepository;
use Tickets\Festival\Response\TicketTypeDto;

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
