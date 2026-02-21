<?php

namespace Tickets\TypesOfPayment\Repositories;

use App\Models\Ordering\InfoForOrder\TypesOfPaymentModel;
use Shared\Domain\Criteria\Filters;
use Tickets\TypesOfPayment\Dto\TypesOfPaymentDto;

class InMemoryMySqlTypesOfPaymentRepository implements TypesOfPaymentRepositoryInterface
{
    public function __construct(
        private TypesOfPaymentModel $model
    )
    {
    }

    public function getList(Filters $filters): array
    {
        $builder = $this->model;

        foreach ($filters as $filter) {
            if (null !== $filter->value()->value()) {
                $builder = $builder->where(
                    $filter->field()->value(),
                    $filter->operator()->value(),
                    $filter->value()->value()
                );
            }
        }

        $rawData = $builder
            ->orderBy('created_at', 'DESC')
            ->get()
            ->toArray();
        $result = [];
        foreach ($rawData as $datum) {
            $result[] = TypesOfPaymentDto::fromState($datum);
        }

        return $result;
    }
}
