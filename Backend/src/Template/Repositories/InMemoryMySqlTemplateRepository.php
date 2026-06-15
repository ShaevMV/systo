<?php

declare(strict_types=1);

namespace Tickets\Template\Repositories;

use App\Models\Template\TemplateModel;
use Illuminate\Support\Collection;
use Shared\Domain\Criteria\Filters;
use Shared\Domain\Criteria\Order;
use Shared\Domain\Filter\FilterBuilder;
use Shared\Domain\ValueObject\Uuid;
use Tickets\Template\Dto\TemplateDto;

class InMemoryMySqlTemplateRepository implements TemplateRepositoryInterface
{
    public function __construct(
        private TemplateModel $model,
    ) {
    }

    public function findActive(string $slug, string $kind): ?TemplateDto
    {
        $row = $this->model::query()
            ->whereSlug($slug)
            ->whereKind($kind)
            ->whereActive(true)
            ->first();

        return $row === null ? null : TemplateDto::fromState($row->toArray());
    }

    public function findBySlugKind(string $slug, string $kind): ?TemplateDto
    {
        $row = $this->model::query()
            ->whereSlug($slug)
            ->whereKind($kind)
            ->first();

        return $row === null ? null : TemplateDto::fromState($row->toArray());
    }

    public function getList(Filters $filters, Order $orderBy): Collection
    {
        $build = FilterBuilder::build($this->model::query(), $filters);

        if ($orderBy->orderBy()->value()) {
            $build = $build->orderBy(
                $orderBy->orderBy()->value(),
                $orderBy->orderType()->value(),
            );
        }

        return $build->get()
            ->map(fn (TemplateModel $model) => TemplateDto::fromState($model->toArray()));
    }

    public function getItem(Uuid $id): TemplateDto
    {
        if (! $row = $this->model::whereId($id->value())->first()) {
            throw new \DomainException('Шаблон не найден ' . $id->value());
        }

        return TemplateDto::fromState($row->toArray());
    }

    public function create(TemplateDto $data): bool
    {
        // create() (а не insert) — чтобы сработали касты active/is_system => boolean и авто-timestamps.
        return (bool) $this->model::create($data->toArrayForCreate());
    }

    public function editItem(Uuid $id, TemplateDto $data): bool
    {
        if (! $row = $this->model::whereId($id->value())->first()) {
            throw new \DomainException('Шаблон не найден ' . $id->value());
        }

        return $row->fill($data->toArrayForEdit())->save();
    }

    public function activate(Uuid $id, bool $active): bool
    {
        return (bool) $this->model::whereId($id->value())->update(['active' => $active]);
    }
}
