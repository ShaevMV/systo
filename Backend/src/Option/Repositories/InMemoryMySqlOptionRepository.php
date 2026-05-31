<?php

declare(strict_types=1);

namespace Tickets\Option\Repositories;

use App\Models\Option\OptionModel;
use App\Models\Option\OptionPriceModel;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Shared\Domain\Criteria\Filters;
use Shared\Domain\Criteria\Order;
use Shared\Domain\Filter\FilterBuilder;
use Shared\Domain\ValueObject\Uuid;
use Tickets\Option\Dto\OptionDto;
use Tickets\Option\Dto\OptionForTicketTypeView;
use Tickets\Option\Dto\OptionTicketTypeBindingDto;

class InMemoryMySqlOptionRepository implements OptionRepositoryInterface
{
    public function __construct(
        private OptionModel $model
    ) {
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
            ->map(fn (OptionModel $model) => OptionDto::fromState($model->toArray()));
    }

    public function getItem(Uuid $id): OptionDto
    {
        if (! $rawData = $this->model::whereId($id->value())->first()) {
            throw new \DomainException('Опция не найдена '.$id->value());
        }

        return OptionDto::fromState($rawData->toArray());
    }

    public function create(OptionDto $data): bool
    {
        DB::beginTransaction();
        try {
            $this->model->insert(
                array_merge($data->toArrayForCreate(), [
                    'created_at' => (string) (new Carbon),
                    'updated_at' => (string) (new Carbon),
                ])
            );
            DB::commit();

            return true;
        } catch (\Throwable $exception) {
            DB::rollBack();
            throw $exception;
        }
    }

    public function editItem(Uuid $id, OptionDto $data): bool
    {
        if (! $rawData = $this->model::whereId($id->value())->first()) {
            throw new \DomainException('Опция не найдена '.$id->value());
        }

        return $rawData->fill($data->toArrayForEdit())->save();
    }

    public function remove(Uuid $id): bool
    {
        return (bool) $this->model::whereId($id->value())->delete();
    }

    public function syncTicketTypes(Uuid $optionId, array $bindings): void
    {
        /** @var OptionModel|null $option */
        $option = $this->model::whereId($optionId->value())->first();

        if (! $option) {
            throw new \DomainException('Опция не найдена '.$optionId->value());
        }

        $syncPayload = [];
        foreach ($bindings as $binding) {
            $syncPayload[$binding->getTicketTypeId()->value()] = [
                'description' => $binding->getDescription(),
            ];
        }

        $option->ticketTypes()->sync($syncPayload);
    }

    public function getTicketTypeBindings(Uuid $optionId): array
    {
        /** @var OptionModel|null $option */
        $option = $this->model::whereId($optionId->value())->first();

        if (! $option) {
            throw new \DomainException('Опция не найдена '.$optionId->value());
        }

        return $option->ticketTypes()
            ->get()
            ->map(static fn ($ticketType) => new OptionTicketTypeBindingDto(
                new Uuid($ticketType->id),
                $ticketType->pivot->description ?? null,
            ))
            ->all();
    }

    public function getActiveOptionsForTicketType(Uuid $ticketTypeId): array
    {
        $rows = $this->model::query()
            ->select([
                'options.id',
                'options.name',
                'options.active',
                'option_ticket_type.description',
            ])
            ->selectSub(
                OptionPriceModel::query()
                    ->select('price')
                    ->whereColumn('option_id', 'options.id')
                    ->whereDate('before_date', '>=', Carbon::now()->toDateString())
                    ->whereNull('deleted_at')
                    ->orderBy('before_date', 'asc')
                    ->limit(1),
                'price'
            )
            ->join('option_ticket_type', 'option_ticket_type.option_id', '=', 'options.id')
            ->where('options.active', true)
            ->where('option_ticket_type.ticket_type_id', $ticketTypeId->value())
            ->get();

        return $rows
            ->filter(static fn ($row) => $row->price !== null)
            ->map(static fn ($row) => OptionForTicketTypeView::fromState([
                'id' => $row->id,
                'name' => $row->name,
                'price' => (int) $row->price,
                'description' => $row->description,
                'active' => (bool) $row->active,
            ]))
            ->values()
            ->all();
    }
}
