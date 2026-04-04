<?php

declare(strict_types=1);

namespace Tickets\QuestionnaireType\Repositories;

use App\Models\Questionnaire\QuestionnaireTypeModel;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Shared\Domain\Criteria\FilterOperator;
use Shared\Domain\Criteria\Filters;
use Shared\Domain\Criteria\Order;
use Shared\Domain\Filter\FilterBuilder;
use Shared\Domain\ValueObject\Uuid;
use Tickets\QuestionnaireType\Dto\QuestionnaireTypeDto;

class InMemoryMySqlQuestionnaireTypeRepository implements QuestionnaireTypeRepositoryInterface
{
    public function __construct(
        private QuestionnaireTypeModel $model
    )
    {
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
            ->each(fn(QuestionnaireTypeModel $model) => QuestionnaireTypeDto::fromState($model->toArray()));
    }

    public function getItem(Uuid $id): QuestionnaireTypeDto
    {
        if (!$rawData = $this->model::whereId($id->value())->first()) {
            throw new \DomainException('Тип анкеты не найден ' . $id->value());
        }

        return QuestionnaireTypeDto::fromState($rawData->toArray());
    }

    public function create(QuestionnaireTypeDto $data): bool
    {
        DB::beginTransaction();
        try {
            $this->model->insert(
                array_merge($data->toArrayForCreate(),
                    [
                        'created_at' => (string)(new Carbon()),
                        'updated_at' => (string)(new Carbon()),
                    ]
                ));
            DB::commit();
            return true;
        } catch (\Throwable $exception) {
            DB::rollBack();
            throw $exception;
        }
    }

    public function editItem(Uuid $id, QuestionnaireTypeDto $data): bool
    {
        if (!$rawData = $this->model::whereId($id->value())->first()) {
            throw new \DomainException('Тип анкеты не найден ' . $id->value());
        }

        return $rawData->fill($data->toArrayForEdit())->save();
    }

    public function remove(Uuid $id): bool
    {
        return (bool)$this->model::whereId($id->value())->delete();
    }
}
