<?php

declare(strict_types=1);

namespace Baza\Festival\Repositories;

use App\Models\FestivalModel;

/**
 * Реестр фестивалей на Vhod (TD-48, PR-1). БД — ТОЛЬКО здесь (Dependency Rule).
 *
 * Стиль проекций — как InMemoryMySqlShiftScheduleRepository: возвращаем массивы,
 * без утечки Eloquent-моделей наружу.
 */
class InMemoryMySqlFestivalRepository implements FestivalRepositoryInterface
{
    public function __construct(
        private FestivalModel $model,
    ) {
    }

    public function all(): array
    {
        return $this->model::query()
            ->orderByDesc('year')
            ->orderBy('name')
            ->get()
            ->map(fn (FestivalModel $m): array => $this->project($m))
            ->all();
    }

    public function listActiveForKpp(): array
    {
        return $this->model::query()
            ->where('active_for_kpp', true)
            ->orderByDesc('year')
            ->orderBy('name')
            ->get()
            ->map(fn (FestivalModel $m): array => $this->project($m))
            ->all();
    }

    public function find(string $id): ?array
    {
        /** @var FestivalModel|null $model */
        $model = $this->model::find($id);

        return $model === null ? null : $this->project($model);
    }

    public function exists(string $id): bool
    {
        return $this->model::whereKey($id)->exists();
    }

    public function nameFor(string $id): ?string
    {
        $name = $this->model::whereKey($id)->value('name');

        return $name !== null ? (string) $name : null;
    }

    public function namesByIds(array $ids): array
    {
        $ids = array_values(array_unique(array_filter($ids, static fn ($v): bool => $v !== null && $v !== '')));
        if ($ids === []) {
            return [];
        }

        return $this->model::query()
            ->whereIn('id', $ids)
            ->pluck('name', 'id')
            ->map(static fn ($name): string => (string) $name)
            ->all();
    }

    public function setActiveForKpp(string $id, bool $active): bool
    {
        $model = $this->model::find($id);
        if ($model === null) {
            return false;
        }

        $model->active_for_kpp = $active;

        return (bool) $model->save();
    }

    /**
     * @return array<string, mixed>
     */
    private function project(FestivalModel $m): array
    {
        return [
            'id' => (string) $m->id,
            'name' => (string) $m->name,
            'year' => $m->year !== null ? (int) $m->year : null,
            'active' => (bool) $m->active,
            'active_for_kpp' => (bool) $m->active_for_kpp,
        ];
    }
}
