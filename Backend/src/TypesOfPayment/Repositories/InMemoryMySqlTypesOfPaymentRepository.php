<?php

namespace Tickets\TypesOfPayment\Repositories;
use App\Models\Festival\TicketTypeFestivalModel;
use App\Models\Festival\TypesOfPaymentModel;
use App\Models\User;
use Carbon\Carbon;
use Doctrine\DBAL\Exception;
use Illuminate\Database\Query\JoinClause;
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
        $builder = $this->model::leftJoin(User::TABLE, function (JoinClause $join) {
            $join->on(
                User::TABLE . '.id',
                '=',
                $this->model::TABLE . '.user_external_id'
            );
        })->select([$this->model::TABLE.".*", User::TABLE.'.email as email_seller']);

        return FilterBuilder::build($builder, $filters)
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
        $data = $paymentDto->toArrayForCreate();
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
