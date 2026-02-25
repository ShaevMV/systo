<?php

declare(strict_types=1);

namespace Tickets\Festival\Application\GetInfoForOrder;

use Shared\Domain\Bus\Query\QueryHandler;
use Tickets\Festival\Repositories\TypesOfPaymentInterface;
use Tickets\Festival\Response\ListTypesOfPaymentDto;

class TypesOfPaymentQueryHandler implements QueryHandler
{
    public function __construct(
        private TypesOfPaymentInterface       $typesOfPayment,
    )
    {
    }

    public function __invoke(TypesOfPaymentQuery $query): ListTypesOfPaymentDto
    {
        return new ListTypesOfPaymentDto(
            $this->typesOfPayment->getList(
                $query->isForAdmin(),
                $query->getTicketTypeId(),
            )
        );
    }
}
