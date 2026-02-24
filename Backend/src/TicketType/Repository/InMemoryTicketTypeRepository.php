<?php

declare(strict_types=1);

namespace Tickets\TicketType\Repository;

use App\Models\Festival\FestivalModel;
use App\Models\Festival\TicketTypeFestivalModel;
use App\Models\Festival\TicketTypesModel;
use App\Models\Festival\TicketTypesPriceModel;
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
use Tickets\TicketType\Application\GetList\TicketTypeGetListFilter;
use Tickets\TicketType\Dto\TicketTypeDto;

class InMemoryTicketTypeRepository implements TicketTypeRepositoryInterface
{
    public function __construct(
        private TicketTypesModel        $model,
        private TicketTypeFestivalModel $ticketTypeFestivalModel,
    )
    {
    }


    private function getFilterValues(TicketTypeGetListFilter $filterQuery): array
    {
        return [
            [
                'field' => TicketTypesModel::TABLE . '.name',
                'operator' => FilterOperator::LIKE,
                'value' => $filterQuery->getName(),
            ],
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
            [
                'field' => TicketTypeFestivalModel::TABLE . '.festival_id',
                'operator' => FilterOperator::EQUAL,
                'value' => $filterQuery->getFestivalId()?->value(),
            ],
        ];
    }

    public function getList(TicketTypeGetListFilter $filters, Order $orderBy): Collection
    {
        $build = $this->model::leftJoin(TicketTypeFestivalModel::TABLE, function (JoinClause $join) {
            $join->on(
                TicketTypeFestivalModel::TABLE . '.ticket_type_id',
                '=',
                $this->model::TABLE . '.id'
            );
        })->leftJoin(FestivalModel::TABLE, function (JoinClause $join) {
            $join->on(
                TicketTypeFestivalModel::TABLE . '.festival_id',
                '=',
                FestivalModel::TABLE . '.id'
            );
        })->select([
            $this->model::TABLE . '.*',
            FestivalModel::TABLE . '.id as festival_id',
            DB::raw("CONCAT_WS(' ', " . FestivalModel::TABLE . ".name, " . FestivalModel::TABLE . ".year) as festival_name"),
            DB::raw('COALESCE(
            (SELECT price
             FROM ' . TicketTypesPriceModel::TABLE . '
             WHERE ' . TicketTypesPriceModel::TABLE . '.ticket_type_id = ' . $this->model::TABLE . '.id
             AND before_date >= CURDATE()
             ORDER BY before_date ASC
             LIMIT 1),
            ' . $this->model::TABLE . '.price
            ) as current_price')
        ]);

        $result = FilterBuilder::build($build, Filters::fromValues($this->getFilterValues($filters)));

        if ($orderBy->orderBy()->value()) {
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
        if (!$this->model::whereId($id->value())->exists()) {
            throw new \DomainException('TypesOfPayment not found ' . $id->value());
        }
        $rawData = $this->model::leftJoin(TicketTypeFestivalModel::TABLE, function (JoinClause $join) {
            $join->on(
                TicketTypeFestivalModel::TABLE . '.ticket_type_id',
                '=',
                $this->model::TABLE . '.id'
            );
        })->select([
            $this->model::TABLE . '.*',
            TicketTypeFestivalModel::TABLE . '.festival_id'
        ])->where($this->model::TABLE . '.id', '=', $id->value())
            ->first();

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
        if ($data->getFestivalId()) {
            $this->sendFestival($id, $data->getFestivalId());
        }

        return $rawData->fill($data->toArrayForEdit())->save();
    }

    private function sendFestival(Uuid $ticketTypeId, Uuid $festivalId): void
    {
        if(!$rawData = $this->ticketTypeFestivalModel::whereTicketTypeId($ticketTypeId->value())->first()){
            $rawData = new TicketTypeFestivalModel();
            $rawData->ticket_type_id = $ticketTypeId->value();
        };

        $rawData->festival_id = $festivalId->value();
        $rawData->save();
    }

    /**
     * @throws \Throwable
     * @throws JsonException
     * @throws Exception
     */
    public function create(TicketTypeDto $data): bool
    {
        $dataAr = $data->toArrayForCreate();
        try {
            DB::beginTransaction();
            $this->model->insert(
                array_merge($dataAr,
                    [
                        'created_at' => (string)(new Carbon()),
                        'updated_at' => (string)(new Carbon()),
                    ]
                ));
            if ($data->getFestivalId()) {
                $this->sendFestival(
                    $data->getId(),
                    $data->getFestivalId()
                );
            }
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
