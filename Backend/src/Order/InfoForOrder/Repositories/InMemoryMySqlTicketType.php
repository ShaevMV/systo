<?php

declare(strict_types=1);

namespace Tickets\Order\InfoForOrder\Repositories;

use App\Models\Ordering\InfoForOrder\TicketTypesModel;
use Carbon\Carbon;
use DomainException;
use Illuminate\Database\Query\Builder;
use Tickets\Order\InfoForOrder\Response\TicketTypeDto;
use Tickets\Shared\Domain\ValueObject\Uuid;

class InMemoryMySqlTicketType implements TicketTypeInterface
{
    public function __construct(
        private TicketTypesModel $model,
    )
    {
    }

    public function getList(): array
    {
        $result = [];

        $data = $this->model::with('ticketTypePrice')->get();
        foreach ($data as $item) {
            $result[] = TicketTypeDto::fromState($item->toArray());
        }

        return $result;
    }

    public function getById(Uuid $uuid, ?Carbon $afterDate = null): TicketTypeDto
    {
        $ticketType = $this->model
            ::whereId($uuid->value());
        if (!is_null($afterDate)) {
            $ticketType = $ticketType
                ->with(['ticketTypePrice' => fn($query) => $query->where('before_date', '<=', $afterDate)->orderBy('before_date')->limit(1)->first()]);
        }

        $ticketType = $ticketType->first();

        if (is_null($ticketType)) {
            throw new DomainException('Не найденн тип билета по id ' . $uuid->value());
        }

        return TicketTypeDto::fromState($ticketType->toArray());
    }
}
