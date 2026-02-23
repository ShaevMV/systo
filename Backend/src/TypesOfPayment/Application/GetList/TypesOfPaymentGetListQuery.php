<?php

declare(strict_types=1);

namespace Tickets\TypesOfPayment\Application\GetList;

use Shared\Domain\Bus\Query\Query;
use Shared\Domain\Criteria\Order;
use Shared\Domain\ValueObject\Uuid;

class TypesOfPaymentGetListQuery implements Query
{
    public function __construct(
        private TypesOfPaymentGetListFilter $typesOfPaymentGetListFilter,
        private Order $orderBy,
    )
    {
    }

    public function getTypesOfPaymentGetListFilter(): TypesOfPaymentGetListFilter
    {
        return $this->typesOfPaymentGetListFilter;
    }

    public function getOrderBy(): Order
    {
        return $this->orderBy;
    }
}
