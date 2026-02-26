<?php

namespace Tickets\TypesOfPayment\Repositories;

use App\Models\Festival\TicketTypesModel;
use App\Models\Festival\TypesOfPaymentModel;
use App\Models\User;
use Carbon\Carbon;
use Doctrine\DBAL\Exception;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Nette\Utils\JsonException;
use Shared\Domain\Criteria\FilterOperator;
use Shared\Domain\Criteria\Filters;
use Shared\Domain\Criteria\Order;
use Shared\Domain\Filter\FilterBuilder;
use Shared\Domain\ValueObject\Uuid;
use Tickets\TypesOfPayment\Application\GetList\TypesOfPaymentGetListFilter;
use Tickets\TypesOfPayment\Application\GetList\TypesOfPaymentGetListQuery;
use Tickets\TypesOfPayment\Dto\TypesOfPaymentDto;

class InMemoryMySqlTypesOfPaymentRepository implements TypesOfPaymentRepositoryInterface
{
    public function __construct(
        private TypesOfPaymentModel $model
    )
    {
    }

    public function getList(TypesOfPaymentGetListFilter $filters, Order $orderBy): Collection
    {
        $builder = $this->model::leftJoin(User::TABLE, function (JoinClause $join) {
            $join->on(
                User::TABLE . '.id',
                '=',
                $this->model::TABLE . '.user_external_id'
            );
        })->leftJoin(TicketTypesModel::TABLE, function (JoinClause $join) {
            $join->on(
                TicketTypesModel::TABLE . '.id',
                '=',
                $this->model::TABLE . '.ticket_type_id'
            );
        })->select([
            $this->model::TABLE . ".*",
            User::TABLE . '.email as email_seller',
            TicketTypesModel::TABLE . '.name as ticket_type_name',
        ]);
        if ($orderBy->orderBy()->value()) {
            $builder = $builder->orderBy(
                $orderBy->orderBy()->value(),
                $orderBy->orderType()->value()
            );
        }

        return FilterBuilder::build($builder, Filters::fromValues(
            $this->getFilterValues($filters)
        ))->get()
            ->map(fn(TypesOfPaymentModel $model) => TypesOfPaymentDto::fromState($model->toArray()));
    }

    private function getFilterValues(TypesOfPaymentGetListFilter $filterQuery): array
    {
        return [
            // email
            [
                'field' => TypesOfPaymentModel::TABLE . '.name',
                'operator' => FilterOperator::LIKE,
                'value' => $filterQuery->getName(),
            ],
            // status
            [
                'field' => TypesOfPaymentModel::TABLE . '.active',
                'operator' => FilterOperator::EQUAL,
                'value' => $filterQuery->getActive(),
            ],
            // types_of_payment_id
            [
                'field' => TypesOfPaymentModel::TABLE . '.is_billing',
                'operator' => FilterOperator::EQUAL,
                'value' => $filterQuery->getIsBilling(),
            ],
            [
                'field' => TypesOfPaymentModel::TABLE . '.ticket_type_id',
                'operator' => FilterOperator::EQUAL,
                'value' => $filterQuery->getUserExternal()?->value(),
            ],
        ];
    }

    public function getItem(Uuid $id): TypesOfPaymentDto
    {
        $builder = $this->model::leftJoin(User::TABLE, function (JoinClause $join) {
            $join->on(
                User::TABLE . '.id',
                '=',
                $this->model::TABLE . '.user_external_id'
            );
        })->leftJoin(TicketTypesModel::TABLE, function (JoinClause $join) {
            $join->on(
                TicketTypesModel::TABLE . '.id',
                '=',
                $this->model::TABLE . '.ticket_type_id'
            );
        })->select([
            $this->model::TABLE . ".*",
            User::TABLE . '.email as email_seller',
            TicketTypesModel::TABLE . '.name as ticket_type_name',
        ]);

        if (!$rawData = $builder->where($this->model::TABLE . '.id', $id->value())->first()) {
            throw new \DomainException('TypesOfPayment not found ' . $id->value());
        }

        return TypesOfPaymentDto::fromState($rawData->toArray());
    }

    /**
     * @throws JsonException
     */
    public function editItem(Uuid $id, TypesOfPaymentDto $paymentDto): bool
    {
        if (!$rawData = $this->model::whereId($id->value())->first()) {
            throw new \DomainException('TypesOfPayment not found ' . $id->value());
        }

        return $rawData->fill($paymentDto->toArrayForEdit())->save();
    }

    public function create(TypesOfPaymentDto $paymentDto): bool
    {
        $data = $paymentDto->toArrayForCreate();
        DB::beginTransaction();
        try {

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
