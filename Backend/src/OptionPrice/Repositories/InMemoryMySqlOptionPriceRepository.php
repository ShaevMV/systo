<?php

declare(strict_types=1);

namespace Tickets\OptionPrice\Repositories;

use App\Models\Option\OptionPriceModel;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Shared\Domain\Criteria\Filters;
use Shared\Domain\Criteria\Order;
use Shared\Domain\Filter\FilterBuilder;
use Shared\Domain\ValueObject\Uuid;
use Tickets\OptionPrice\Dto\OptionPriceDto;

class InMemoryMySqlOptionPriceRepository implements OptionPriceRepositoryInterface
{
    public function __construct(
        private OptionPriceModel $model
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
        } else {
            $result = $result->orderBy('before_date', 'asc');
        }

        return $result->get()
            ->map(fn (OptionPriceModel $model) => OptionPriceDto::fromState($model->toArray()));
    }

    public function getItem(Uuid $id): OptionPriceDto
    {
        if (! $rawData = $this->model::whereId($id->value())->first()) {
            throw new \DomainException('Волна цены опции не найдена '.$id->value());
        }

        return OptionPriceDto::fromState($rawData->toArray());
    }

    public function create(OptionPriceDto $data): bool
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

    public function editItem(Uuid $id, OptionPriceDto $data): bool
    {
        if (! $rawData = $this->model::whereId($id->value())->first()) {
            throw new \DomainException('Волна цены опции не найдена '.$id->value());
        }

        return $rawData->fill($data->toArrayForEdit())->save();
    }

    public function remove(Uuid $id): bool
    {
        return (bool) $this->model::whereId($id->value())->delete();
    }
}
