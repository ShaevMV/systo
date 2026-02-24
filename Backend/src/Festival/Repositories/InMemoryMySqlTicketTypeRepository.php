<?php

declare(strict_types=1);

namespace Tickets\Festival\Repositories;

use App\Models\Festival\TicketTypeFestivalModel;
use App\Models\Festival\TicketTypesModel;
use Carbon\Carbon;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\Log;
use Shared\Domain\ValueObject\Uuid;
use Tickets\Festival\Response\TicketTypeDto;

class InMemoryMySqlTicketTypeRepository implements TicketTypeInterfaceRepository
{
    public function __construct(
        private TicketTypesModel $model,
    )
    {
    }

    public function getList(
        Uuid    $festivalId,
        bool    $isAllPrice = false,
        ?Carbon $afterDate = null
    ): array
    {
        $result = [];
        $data = $this->joinFestival($festivalId, $afterDate)
            ->addSelect([TicketTypeFestivalModel::TABLE . '.description'])
            ->where('active', '=', 1);
        Log::info('getList:', [
            'sql' => $data->toSql(),
            'bindings' => $data->getBindings()
        ]);
        $data = $data->get()
            ->toArray();

        foreach ($data as $item) {
            $result[] = TicketTypeDto::fromState($item, $isAllPrice);
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

    public function getNameById(): array
    {
        $result = [];
        foreach ($this->model::whereActive(true)->select(['id', 'name'])->get()->toArray() as $item) {
            $result[$item['id']] = $item['name'];
        }

        return $result;
    }
}
