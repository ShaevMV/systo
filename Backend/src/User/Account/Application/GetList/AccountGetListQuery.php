<?php

declare(strict_types=1);

namespace Tickets\User\Account\Application\GetList;

use Shared\Domain\Bus\Query\Query;
use Shared\Domain\Criteria\Order;

class AccountGetListQuery implements Query
{
    public function __construct(
        private AccountGetListFilter $accountGetListFilter,
        private Order $orderBy
    )
    {
    }

    public function getAccountGetListFilter(): AccountGetListFilter
    {
        return $this->accountGetListFilter;
    }

    public function getOrderBy(): Order
    {
        return $this->orderBy;
    }
}
