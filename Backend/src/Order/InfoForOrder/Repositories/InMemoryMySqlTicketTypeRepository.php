<?php

declare(strict_types=1);

namespace Tickets\Order\InfoForOrder\Repositories;

use App\Models\Festival\FestivalModel;
use App\Models\Ordering\InfoForOrder\TicketTypesModel;
use App\Models\Ordering\InfoForOrder\TicketTypesPriceModel;
use App\Models\Ordering\TicketTypeFestivalModel;
use Carbon\Carbon;
use DomainException;
use Illuminate\Database\Query\JoinClause;
use Tickets\Order\InfoForOrder\Response\TicketTypeDto;
use Shared\Domain\ValueObject\Uuid;
use Illuminate\Database\Eloquent\Builder;

class InMemoryMySqlTicketTypeRepository implements TicketTypeInterfaceRepository
{
    public function __construct(
        private TicketTypesModel $model,
    )
    {
    }

    public function getList(
        Uuid    $festivalId,
        ?Carbon $afterDate = null
    ): array
    {
        $result = [];

        $data = $this->joinFestival($festivalId, $afterDate)
            ->where('active', '=', 1);
        $data = $data->get()
            ->toArray();

        foreach ($data as $item) {
            $result[] = TicketTypeDto::fromState($item);
        }

        return $result;
    }

    private function joinFestival(Uuid $festivalId, ?Carbon $afterDate = null): Builder
    {
        return $this->model
            ->leftJoin(TicketTypeFestivalModel::TABLE, function (JoinClause $join) {
                $join->on(
                    TicketTypeFestivalModel::TABLE . '.ticket_type_id',
                    '=',
                    $this->model::TABLE . '.id'
                );
            })->with([
                'ticketTypePrice' => fn($query) => $query
                    ->where('before_date', '<=', $afterDate ?? new Carbon())
                    ->orderBy('before_date'),
                'festivals',
            ])
            ->where(TicketTypeFestivalModel::TABLE . '.festival_id', '=', $festivalId->value())
            ->select([
                $this->model::TABLE . '.*'
            ])
            ->orderBy($this->model::TABLE . '.sort');
    }

    public function getById(Uuid $uuid, ?Carbon $afterDate = null): TicketTypeDto
    {
        $ticketType = $this->model
            ::whereId($uuid->value())
            ->with('festivals');
        if (!is_null($afterDate)) {
            $ticketType = $ticketType
                ->with(['ticketTypePrice' => fn($query) => $query->where('before_date', '<=', $afterDate)->orderBy('before_date')]);
        }

        $ticketType = $ticketType
            ->first();

        if (is_null($ticketType)) {
            throw new DomainException('Не найден тип билета по id ' . $uuid->value());
        }

        return TicketTypeDto::fromState($ticketType->toArray());
    }

    public function getListPrice(Uuid $festivalId): array
    {
        $result = [];
        $rawResult = $this->joinFestival($festivalId)
            ->get()
            ->toArray();

        return $result;
    }

    public function create(TicketTypeDto $typeDto): bool
    {
        $this->model::create($typeDto->toArray());

        return true;
    }
}
