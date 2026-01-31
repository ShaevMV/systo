<?php

declare(strict_types = 1);

namespace Tickets\Festival\Application\GetInfoForOrder;


use Shared\Domain\Bus\Query\QueryHandler;
use Tickets\Festival\Repositories\TicketTypeInterfaceRepository;
use Tickets\Festival\Response\ListTicketTypeDto;

final class ListTicketTypeQueryHandler implements QueryHandler
{
    public function __construct(
        private TicketTypeInterfaceRepository $ticketType,
    ) {
    }

    public function __invoke(ListTicketTypeQuery $query): ListTicketTypeDto
    {
        return new ListTicketTypeDto(
            $this->ticketType->getList(
                $query->getFestivalId(),
                $query->isAllPrice(),
            )
        );
    }
}
