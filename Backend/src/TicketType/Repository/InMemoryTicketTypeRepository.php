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
use Tickets\TicketType\Dto\FestivalDto;
use Tickets\TicketType\Dto\TicketTypeDto;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class InMemoryTicketTypeRepository implements TicketTypeRepositoryInterface
{
    public function __construct(
        private TicketTypesModel        $model,
        private TicketTypeFestivalModel $ticketTypeFestivalModel,
    )
    {
    }

    private function buildBuilder(Model|Builder $model):Model|Builder
    {
        return $model::leftJoin(TicketTypeFestivalModel::TABLE, function (JoinClause $join) {
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
            TicketTypeFestivalModel::TABLE . '.description as festival_description',
            TicketTypeFestivalModel::TABLE . '.email as festival_email',
            TicketTypeFestivalModel::TABLE . '.pdf as festival_pdf',
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
                'field' => TicketTypesModel::TABLE . '.is_parking',
                'operator' => FilterOperator::EQUAL,
                'value' => $filterQuery->getIsParking(),
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
        $build = $this->buildBuilder($this->model);

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
        $build = $this->buildBuilder($this->model);

        $rawData = $build->where($this->model::TABLE . '.id', '=', $id->value())
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
        if ($data->getFestival()->getId()) {
            $this->sendFestival($id, $data->getFestival());
        }

        return $rawData->fill($data->toArrayForEdit())->save();
    }

    private function sendFestival(Uuid $ticketTypeId, FestivalDto $festivalDto): void
    {
        if(!$rawData = $this->ticketTypeFestivalModel::whereTicketTypeId($ticketTypeId->value())->first()){
            $rawData = new TicketTypeFestivalModel();
            $rawData->ticket_type_id = $ticketTypeId->value();
        };

        $rawData->festival_id = $festivalDto->getId()->value();
        $rawData->description = $festivalDto->getDescription();
        $rawData->email = $festivalDto->getEmail();
        $rawData->pdf = $festivalDto->getPdf();

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
        DB::beginTransaction();
        try {

            $this->model->insert(
                array_merge($dataAr,
                    [
                        'created_at' => (string)(new Carbon()),
                        'updated_at' => (string)(new Carbon()),
                    ]
                ));
            if ($data->getFestival()->getId()) {
                $this->sendFestival(
                    $data->getId(),
                    $data->getFestival()
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
