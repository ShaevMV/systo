<?php

declare(strict_types = 1);

namespace Tickets\Order\InfoForOrder\Application\GetInfoForOrder;

use Carbon\Carbon;
use Tickets\Order\InfoForOrder\Repositories\TicketTypeInterface;
use Tickets\Order\InfoForOrder\Repositories\TypesOfPaymentInterface;
use Tickets\Order\InfoForOrder\Response\InfoForOrderingDto;
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
            $this->ticketType->getList(new Carbon()),
            $this->typesOfPayment->getList(),
        );
    }
}
