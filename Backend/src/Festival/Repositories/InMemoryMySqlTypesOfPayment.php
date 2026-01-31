<?php

declare(strict_types=1);

namespace Tickets\Festival\Repositories;

use App\Models\Festival\TypesOfPaymentModel;
use Tickets\Festival\Response\TypesOfPaymentDto;

class InMemoryMySqlTypesOfPayment implements TypesOfPaymentInterface
{
    public function __construct(
        private TypesOfPaymentModel $model
    )
    {
    }


    public function getList(bool $isAdmin = false): array
    {
        $result = [];
        if (!$isAdmin) {
            $typesOfPayments = $this->model::where('active', '=', true);
        } else {
            $typesOfPayments = $this->model;
        }

        $typesOfPayments = $typesOfPayments->orderBy('sort')
            ->get();

        foreach ($typesOfPayments as $item) {
            $result[] = TypesOfPaymentDto::fromState($item->toArray());
        }

        return $result;
    }
}
