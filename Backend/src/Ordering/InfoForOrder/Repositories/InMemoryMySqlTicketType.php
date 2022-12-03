<?php

declare(strict_types=1);

namespace Tickets\Ordering\InfoForOrder\Repositories;

use App\Models\Tickets\Ordering\InfoForOrder\TicketTypesModel;
use DomainException;
use Tickets\Ordering\InfoForOrder\Response\TicketTypeDto;
use Tickets\Shared\Domain\ValueObject\Uuid;

class InMemoryMySqlTicketType implements TicketTypeInterface
{
    public function __construct(
        private TicketTypesModel $model,
    ) {
    }

    public function getList(): array
    {
        $result = [];
        foreach ($this->model::all() as $item) {
            $result[] = TicketTypeDto::fromState($item->toArray());
        }

        return $result;
    }

    public function getById(Uuid $uuid): TicketTypeDto
    {
        if (is_null($ticketType = $this->model->whereId($uuid->value())->first())) {
            throw new DomainException('Не найденн тип билета по id '.$uuid->value());
        }

        return TicketTypeDto::fromState($ticketType->toArray());
    }
}
