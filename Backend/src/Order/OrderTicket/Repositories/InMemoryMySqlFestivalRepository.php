<?php

namespace Tickets\Order\OrderTicket\Repositories;

use App\Models\Festival\FestivalModel;
use App\Models\Festival\TicketTypesModel;
use Shared\Domain\ValueObject\Uuid;
use Tickets\Order\OrderTicket\Dto\Festival\FestivalDto;

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

        foreach ($ticketTypesModelItem->festivals as $value) {
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
