<?php

declare(strict_types=1);

namespace Tickets\Order\InfoForOrder\Application\GetInfoForOrder;

use Shared\Domain\Bus\Query\QueryHandler;
use Tickets\Order\InfoForOrder\Repositories\TypesOfPaymentInterface;
use Tickets\Order\InfoForOrder\Response\ListTypesOfPaymentDto;

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
            $this->typesOfPayment->getList($query->isForAdmin())
        );
    }
}
