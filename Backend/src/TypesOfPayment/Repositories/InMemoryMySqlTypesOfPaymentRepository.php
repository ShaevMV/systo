<?php

namespace Tickets\TypesOfPayment\Repositories;

use App\Models\Ordering\InfoForOrder\TypesOfPaymentModel;
use Illuminate\Support\Collection;
use Nette\Utils\JsonException;
use Shared\Domain\Criteria\Filters;
use Shared\Domain\Filter\FilterBuilder;
use Shared\Domain\ValueObject\Uuid;
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

    public function getItem(Uuid $id): TypesOfPaymentDto
    {
        if(!$rawData = $this->model::whereId($id->value())->first()) {
            throw new \DomainException('TypesOfPayment not found ' . $id->value());
        }

        return TypesOfPaymentDto::fromState($rawData->toArray());
    }

    /**
     * @throws JsonException
     */
    public function editItem(Uuid $id, TypesOfPaymentDto $paymentDto): bool
    {
        if(!$rawData = $this->model::whereId($id->value())->first()) {
            throw new \DomainException('TypesOfPayment not found ' . $id->value());
        }

        return $rawData->update($paymentDto->toArray());
    }

    public function remove(Uuid $id): bool
    {
        return $this->model::whereId($id->value())->delete() ?? false;
    }
}
