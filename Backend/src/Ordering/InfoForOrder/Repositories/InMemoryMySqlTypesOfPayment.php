<?php

declare(strict_types=1);

namespace Tickets\Ordering\InfoForOrder\Repositories;

use App\Models\Tickets\Ordering\InfoForOrder\Models\TypesOfPayment;
use Tickets\Ordering\InfoForOrder\Response\TypesOfPaymentDto;

final class InMemoryMySqlTypesOfPayment implements TypesOfPaymentInterface
{
    public function __construct(
        private TypesOfPayment $model
    ) {
    }


    public function getList(): array
    {
        $result = [];
        foreach ($this->model::all() as $item) {
            $result[] = TypesOfPaymentDto::fromState($item->toArray());
        }

        return $result;
    }
}
