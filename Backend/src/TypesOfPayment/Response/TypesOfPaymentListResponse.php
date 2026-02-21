<?php

declare(strict_types=1);

namespace Tickets\TypesOfPayment\Response;

use Shared\Domain\Bus\Query\Response;

class TypesOfPaymentListResponse implements Response
{
    public function __construct(
        private array $typesOfPaymentList,
    )
    {
    }

    public function getTypesOfPaymentList(): array
    {
        return $this->typesOfPaymentList;
    }
}
