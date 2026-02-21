<?php

declare(strict_types=1);

namespace Tickets\TypesOfPayment\Response;

use Illuminate\Support\Collection;
use Shared\Domain\Bus\Query\Response;

class TypesOfPaymentListResponse implements Response
{
    public function __construct(
        private Collection $typesOfPaymentList,
    )
    {
    }

    public function getTypesOfPaymentList(): Collection
    {
        return $this->typesOfPaymentList;
    }
}
