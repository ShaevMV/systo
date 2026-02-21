<?php

namespace Tickets\TypesOfPayment\Repositories;

use App\Models\Ordering\InfoForOrder\TypesOfPaymentModel;
use Shared\Domain\Criteria\Filters;
use Shared\Domain\Filter\FilterBuilder;
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
        $rawData = FilterBuilder::build($this->model, $filters)
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
