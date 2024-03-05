<?php

declare(strict_types=1);

namespace Tickets\Order\InfoForOrder\Repositories;

use App\Models\Ordering\InfoForOrder\TypesOfPaymentModel;
use Tickets\Order\InfoForOrder\Response\TypesOfPaymentDto;

class InMemoryMySqlTypesOfPayment implements TypesOfPaymentInterface
{
    public function __construct(
        private TypesOfPaymentModel $model
    ) {
    }


    public function getList(): array
    {
        $result = [];
        foreach ($this->model::where('active','=',true)->get() as $item) {
            $result[] = TypesOfPaymentDto::fromState($item->toArray());
        }

        return $result;
    }
}
