<?php

namespace Tickets\TypesOfPayment\Repositories;

use App\Models\Ordering\InfoForOrder\TypesOfPaymentModel;
use Illuminate\Support\Collection;
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

    public function getList(Filters $filters): Collection
    {
        return FilterBuilder::build($this->model, $filters)
            ->orderBy('created_at', 'DESC')
            ->get()
            ->each(fn(TypesOfPaymentModel $model) => TypesOfPaymentDto::fromState($model->toArray()));
    }
}
