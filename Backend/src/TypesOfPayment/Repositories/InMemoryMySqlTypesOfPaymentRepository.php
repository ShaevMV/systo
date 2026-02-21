<?php

namespace Tickets\TypesOfPayment\Repositories;
use App\Models\Festival\TypesOfPaymentModel;
use Carbon\Carbon;
use Doctrine\DBAL\Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
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

        return $rawData->fill($paymentDto->toArrayForEdit())->save();
    }

    public function create(TypesOfPaymentDto $paymentDto): bool
    {
        $data = $paymentDto->toArray();
        try {
            DB::beginTransaction();
            $this->model->insert(
                array_merge($data,
                    [
                        'created_at' => (string)(new Carbon()),
                        'updated_at' => (string)(new Carbon()),
                    ]
                ));
            DB::commit();
            return true;
        } catch (Exception $exception) {
            DB::rollBack();
            throw $exception;
        }
    }

    public function remove(Uuid $id): bool
    {
        return $this->model::whereId($id->value())->delete() ?? false;
    }
}
