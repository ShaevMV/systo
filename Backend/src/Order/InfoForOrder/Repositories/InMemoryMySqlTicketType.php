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

    public function getList(Carbon $afterDate): array
    {
        $result = [];

        $data = $this->model::with('ticketTypePrice')
            ->with(['ticketTypePrice' => fn($query) => $query->where('before_date', '<=', $afterDate)->orderBy('before_date')])
            ->get()->toArray();
        foreach ($data as $item) {
            $result[] = TicketTypeDto::fromState($item);
        }

        return $result;
    }

    public function getById(Uuid $uuid, ?Carbon $afterDate = null): TicketTypeDto
    {
        $ticketType = $this->model
            ::whereId($uuid->value());
        if (!is_null($afterDate)) {
            $ticketType = $ticketType
                ->with(['ticketTypePrice' => fn($query) => $query->where('before_date', '<=', $afterDate)->orderBy('before_date')]);
        }

        $ticketType = $ticketType->first();

        if (is_null($ticketType)) {
            throw new DomainException('Не найденн тип билета по id ' . $uuid->value());
        }

        return TicketTypeDto::fromState($ticketType->toArray());
    }

    public function getListPrice(): array
    {
        $result = [];
        $rawResult = $this->model
            ->with('ticketTypePrice')
            ->get()
            ->toArray();

        foreach ($rawResult as $item) {
            $data = $item;
            unset($data['ticket_type_price']);
            $result[] = TicketTypeDto::fromState($data);
            if (count($item['ticket_type_price']) > 0) {
                foreach ($item['ticket_type_price'] as $value) {
                    $data['price'] = $value['price'];
                    $result[] = TicketTypeDto::fromState($data);
                }
            }
        }

        return $result;
    }
}
