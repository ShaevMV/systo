<?php

declare(strict_types=1);

namespace Tickets\TicketType\Repository;

use App\Models\Festival\TicketTypesModel;
use Carbon\Carbon;
use Doctrine\DBAL\Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Nette\Utils\JsonException;
use Shared\Domain\Criteria\FilterOperator;
use Shared\Domain\Criteria\Filters;
use Shared\Domain\Criteria\Order;
use Shared\Domain\Filter\FilterBuilder;
use Shared\Domain\ValueObject\Uuid;
use Tickets\TicketType\Application\GetList\TicketTypeGetListFilter;
use Tickets\TicketType\Dto\TicketTypeDto;

class InMemoryTicketTypeRepository implements TicketTypeRepositoryInterface
{
    public function __construct(
        private TicketTypesModel $model,
    )
    {
    }


    private function getFilterValues(TicketTypeGetListFilter $filterQuery): array
    {
        return [
            // email
            [
                'field' => TicketTypesModel::TABLE . '.name',
                'operator' => FilterOperator::LIKE,
                'value' => '%' . $filterQuery->getName() . '%',
            ],
            // status
            [
                'field' => TicketTypesModel::TABLE . '.price',
                'operator' => FilterOperator::EQUAL,
                'value' => $filterQuery->getPrice(),
            ],
            // types_of_payment_id
            [
                'field' => TicketTypesModel::TABLE . '.active',
                'operator' => FilterOperator::EQUAL,
                'value' => $filterQuery->getActive(),
            ],
            [
                'field' => TicketTypesModel::TABLE . '.is_live_ticket',
                'operator' => FilterOperator::EQUAL,
                'value' => $filterQuery->getIsLiveTicket(),
            ],
        ];
    }

    public function getList(TicketTypeGetListFilter $filters, Order $orderBy): Collection
    {
        $result = FilterBuilder::build($this->model, Filters::fromValues($this->getFilterValues($filters)));

        if($orderBy->orderBy()->value()) {
            $result = $result->orderBy(
                $orderBy->orderBy()->value(),
                $orderBy->orderType()->value()
            );
        }
        return $result->get()
            ->each(fn(TicketTypesModel $model) => TicketTypeDto::fromState($model->toArray()));
    }

    public function getItem(Uuid $id): TicketTypeDto
    {
        if (!$rawData = $this->model::whereId($id->value())->first()) {
            throw new \DomainException('TypesOfPayment not found ' . $id->value());
        }

        return TicketTypeDto::fromState($rawData->toArray());
    }

    /**
     * @throws JsonException
     */
    public function editItem(Uuid $id, TicketTypeDto $data): bool
    {
        if (!$rawData = $this->model::whereId($id->value())->first()) {
            throw new \DomainException('TypesOfPayment not found ' . $id->value());
        }

        return $rawData->fill($data->toArrayForEdit())->save();
    }

    /**
     * @throws \Throwable
     * @throws JsonException
     * @throws Exception
     */
    public function create(TicketTypeDto $data): bool
    {
        $data = $data->toArray();
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
        return (bool)$this->model::whereId($id->value())->delete();
    }
}
