<?php

declare(strict_types=1);

namespace Tickets\Location\Repository;

use App\Models\Location\LocationModel;
use Illuminate\Support\Collection;
use Shared\Domain\Criteria\FilterOperator;
use Shared\Domain\Criteria\Order;
use Shared\Domain\Filter\FilterBuilder;
use Shared\Domain\ValueObject\Uuid;
use Tickets\Location\Application\GetList\LocationGetListFilter;
use Tickets\Location\Dto\LocationDto;

class InMemoryMySqlLocationRepository implements LocationRepositoryInterface
{
    public function __construct(
        private LocationModel  $model,
        private FilterBuilder  $filterBuilder,
    ) {
    }

    public function getList(LocationGetListFilter $filters, Order $orderBy): Collection
    {
        $build = $this->model::query();

        $filterValues = [
            [
                'field' => LocationModel::TABLE . '.name',
                'operator' => FilterOperator::LIKE,
                'value' => $filters->getName(),
            ],
            [
                'field' => LocationModel::TABLE . '.active',
                'operator' => FilterOperator::EQUAL,
                'value' => $filters->getActive(),
            ],
            [
                'field' => LocationModel::TABLE . '.festival_id',
                'operator' => FilterOperator::EQUAL,
                'value' => $filters->getFestivalId()?->value(),
            ],
        ];

        foreach ($filterValues as $filter) {
            if (!empty($filter['value']) || $filter['value'] === false) {
                $build->where($filter['field'], $filter['operator'], $filter['value']);
            }
        }

        if (!$orderBy->isNone()) {
            $build->orderBy($orderBy->orderBy()->value(), $orderBy->orderType()->value());
        } else {
            $build->orderBy(LocationModel::TABLE . '.sort', 'asc');
        }

        return $build->get()
            ->each(fn(LocationModel $model) => LocationDto::fromState($model->toArray()));
    }

    public function getItem(Uuid $id): LocationDto
    {
        /** @var LocationModel $model */
        $model = $this->model::query()->findOrFail($id->value());

        return LocationDto::fromState($model->toArray());
    }

    public function create(LocationDto $data): bool
    {
        $this->model::create([
            'id'                    => $data->getId()->value(),
            'festival_id'           => $data->getFestivalId()->value(),
            'name'                  => $data->getName(),
            'description'           => $data->getDescription(),
            'questionnaire_type_id' => $data->getQuestionnaireTypeId()?->value(),
            'email_template'        => $data->getEmailTemplate(),
            'pdf_template'          => $data->getPdfTemplate(),
            'active'                => $data->isActive(),
            'sort'                  => $data->getSort(),
        ]);

        return true;
    }

    public function editItem(Uuid $id, LocationDto $data): bool
    {
        $this->model::where('id', $id->value())->update([
            'festival_id'           => $data->getFestivalId()->value(),
            'name'                  => $data->getName(),
            'description'           => $data->getDescription(),
            'questionnaire_type_id' => $data->getQuestionnaireTypeId()?->value(),
            'email_template'        => $data->getEmailTemplate(),
            'pdf_template'          => $data->getPdfTemplate(),
            'active'                => $data->isActive(),
            'sort'                  => $data->getSort(),
        ]);

        return true;
    }

    public function remove(Uuid $id): bool
    {
        $this->model::where('id', $id->value())->delete();

        return true;
    }
}
