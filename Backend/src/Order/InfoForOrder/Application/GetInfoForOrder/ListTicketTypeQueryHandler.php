<?php

declare(strict_types = 1);

namespace Tickets\Order\InfoForOrder\Application\GetInfoForOrder;

use Carbon\Carbon;
use Tickets\Order\InfoForOrder\Repositories\TicketTypeInterfaceRepository;
use Tickets\Order\InfoForOrder\Repositories\TypesOfPaymentInterface;
use Tickets\Order\InfoForOrder\Response\InfoForOrderingDto;
use Shared\Domain\Bus\Query\QueryHandler;
use Tickets\Order\InfoForOrder\Response\ListTicketTypeDto;

final class ListTicketTypeQueryHandler implements QueryHandler
{
    public function __construct(
        private TicketTypeInterfaceRepository $ticketType,
    ) {
    }

    public function __invoke(ListTicketTypeQuery $query): ListTicketTypeDto
    {
        return new ListTicketTypeDto(
            $this->ticketType->getList($query->getFestivalId())
        );
    }
}
