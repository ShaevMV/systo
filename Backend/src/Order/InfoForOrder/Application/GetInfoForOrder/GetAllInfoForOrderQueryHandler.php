<?php

declare(strict_types = 1);

namespace Tickets\Ordering\InfoForOrder\Application\GetInfoForOrder;

use Tickets\Ordering\InfoForOrder\Repositories\TicketTypeInterface;
use Tickets\Ordering\InfoForOrder\Repositories\TypesOfPaymentInterface;
use Tickets\Ordering\InfoForOrder\Response\InfoForOrderingDto;
use Tickets\Shared\Domain\Bus\Query\QueryHandler;

final class GetAllInfoForOrderQueryHandler implements QueryHandler
{
    public function __construct(
        private TicketTypeInterface $ticketType,
        private TypesOfPaymentInterface $typesOfPayment,
    ) {
    }

    public function __invoke(GetAllInfoForOrderQuery $query): InfoForOrderingDto
    {
        return new InfoForOrderingDto(
            $this->ticketType->getList(),
            $this->typesOfPayment->getList(),
        );
    }
}
