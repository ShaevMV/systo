<?php

declare(strict_types=1);

namespace Tickets\TypesOfPayment\Response;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Shared\Domain\Bus\Query\Response;
use Tickets\TypesOfPayment\Dto\TypesOfPaymentDto;

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

    public function getTypesOfPaymentListToArray(): array
    {
        return $this->typesOfPaymentList->map(fn(TypesOfPaymentDto $typesOfPaymentDto) => $typesOfPaymentDto->toArray())->toArray();
    }
}
