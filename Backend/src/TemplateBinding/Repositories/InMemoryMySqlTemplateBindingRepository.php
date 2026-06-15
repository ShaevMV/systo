<?php

declare(strict_types=1);

namespace Tickets\TemplateBinding\Repositories;

use App\Models\Template\TemplateBindingModel;
use App\Models\Template\TemplateModel;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Shared\Domain\ValueObject\Uuid;
use Tickets\TemplateBinding\Dto\TemplateBindingDto;

class InMemoryMySqlTemplateBindingRepository implements TemplateBindingRepositoryInterface
{
    public function __construct(
        private TemplateBindingModel $model,
    ) {
    }

    /** Запрос привязок с подтянутыми slug'ами шаблонов (email/pdf). */
    private function withSlugs(): Builder
    {
        return $this->model::query()
            ->leftJoin(TemplateModel::TABLE . ' as et', 'et.id', '=', TemplateBindingModel::TABLE . '.email_template_id')
            ->leftJoin(TemplateModel::TABLE . ' as pt', 'pt.id', '=', TemplateBindingModel::TABLE . '.pdf_template_id')
            ->select([
                TemplateBindingModel::TABLE . '.*',
                'et.slug as email_slug',
                'pt.slug as pdf_slug',
            ]);
    }

    public function getActiveForResolve(): array
    {
        return $this->withSlugs()
            ->where(TemplateBindingModel::TABLE . '.active', true)
            ->get()
            ->map(fn (TemplateBindingModel $row) => TemplateBindingDto::fromState($row->toArray()))
            ->all();
    }

    public function getList(): Collection
    {
        return $this->withSlugs()
            ->orderByDesc(TemplateBindingModel::TABLE . '.is_default')
            ->orderByDesc(TemplateBindingModel::TABLE . '.created_at')
            ->get()
            ->map(fn (TemplateBindingModel $row) => TemplateBindingDto::fromState($row->toArray()));
    }

    public function getItem(Uuid $id): TemplateBindingDto
    {
        $row = $this->withSlugs()->where(TemplateBindingModel::TABLE . '.id', $id->value())->first();

        if ($row === null) {
            throw new DomainException('Привязка не найдена ' . $id->value());
        }

        return TemplateBindingDto::fromState($row->toArray());
    }

    public function create(TemplateBindingDto $dto): bool
    {
        return (bool) $this->model::create($dto->toArrayForCreate());
    }

    public function editItem(Uuid $id, TemplateBindingDto $dto): bool
    {
        $row = $this->model::whereId($id->value())->first();

        if ($row === null) {
            throw new DomainException('Привязка не найдена ' . $id->value());
        }

        return $row->fill($dto->toArrayForCreate())->save();
    }

    public function remove(Uuid $id): bool
    {
        return (bool) $this->model::whereId($id->value())->delete();
    }

    public function hasActiveDefault(?string $excludeId = null): bool
    {
        return $this->model::query()
            ->where('active', true)
            ->where('is_default', true)
            ->when($excludeId !== null, fn (Builder $q) => $q->where('id', '!=', $excludeId))
            ->exists();
    }
}
