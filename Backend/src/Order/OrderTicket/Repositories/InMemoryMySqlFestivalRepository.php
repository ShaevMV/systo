<?php

namespace Tickets\Order\OrderTicket\Repositories;

use App\Models\Festival\FestivalModel;
use App\Models\Festival\TicketTypesModel;
use Illuminate\Support\Collection;
use Shared\Domain\Criteria\Filters;
use Shared\Domain\Criteria\Order;
use Shared\Domain\Filter\FilterBuilder;
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

    public function create(FestivalDto $dto): bool
    {
        return (bool) $this->model::create($dto->toArrayForCreate());
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

    public function getList(Filters $filters, Order $orderBy): Collection
    {
        $build = $this->model::query();

        $result = FilterBuilder::build($build, $filters);

        if ($orderBy->orderBy()->value()) {
            $result = $result->orderBy(
                $orderBy->orderBy()->value(),
                $orderBy->orderType()->value()
            );
        }

        return $result->get()
            ->map(fn (FestivalModel $model) => FestivalDto::fromState($model->toArray()));
    }

    public function editItem(Uuid $id, FestivalDto $data): bool
    {
        if (!$rawData = $this->model::whereId($id->value())->first()) {
            throw new \DomainException("Фестиваль {$id->value()} не найден");
        }

        return $rawData->fill($data->toArrayForEdit())->save();
    }

    public function remove(Uuid $id): bool
    {
        return (bool) $this->model::whereId($id->value())->delete();
    }
}
