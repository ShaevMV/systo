<?php

declare(strict_types=1);

namespace Tickets\TypesOfPayment\Repositories;

use Illuminate\Support\Collection;
use Shared\Domain\Criteria\Filters;

interface TypesOfPaymentRepositoryInterface
{
    public function getList(Filters $filters): Collection;
}
