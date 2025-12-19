<?php

namespace Tickets\Order\OrderTicket\Repositories;

use App\Models\Festival\FestivalModel;
use App\Models\Ordering\InfoForOrder\TicketTypesModel;
use App\Models\Ordering\TicketTypeFestivalModel;
use Tickets\Order\OrderTicket\Dto\Festival\FestivalDto;
use Shared\Domain\ValueObject\Uuid;

class InMemoryMySqlFestivalRepository implements FestivalRepositoryInterface
{
    public function __construct(
        private FestivalModel    $model,
        private TicketTypesModel $ticketTypesModel,
    )
    {
    }

    public function get(Uuid $id): FestivalDto
    {
        $result = $this->model->find($id->value());

        if (is_null($result)) {
            throw new \DomainException("Фестиваль {$id->value()} не найден");
        }

        return FestivalDto::fromState($result->toArray());
    }

    public function getFestivalByTicketTypeId(Uuid $ticketTypeId): array
    {
        $ticketTypesModelItem = $this->ticketTypesModel::find($ticketTypeId->value());
        if (null === $ticketTypesModelItem) {
            throw new \DomainException('Не найден тип билета ' . $ticketTypeId->value());
        }
        $result = [];

        foreach ($ticketTypesModelItem->festivals()->where('festival_id', '=','9d679bcf-b438-4ddb-ac04-023fa9bff4b8')->get() as $value) {
            $result[] = FestivalDto::fromState($value->toArray());
        }

        return $result;
    }

    /**
     * @return array|FestivalDto[]
     */
    public function getFestivalList(): array
    {
        $list = $this->model->get()?->toArray() ?? [];
        $result = [];
        foreach ($list as $item) {
            $result[] = FestivalDto::fromState($item);
        }

        return $result;
    }
}
