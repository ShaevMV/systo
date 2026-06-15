<?php

declare(strict_types=1);

namespace Tickets\Template\Repositories;

use App\Models\Template\TemplateModel;
use App\Models\Template\TemplateVersionModel;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Shared\Domain\Criteria\Filters;
use Shared\Domain\Criteria\Order;
use Shared\Domain\Filter\FilterBuilder;
use Shared\Domain\ValueObject\Uuid;
use Tickets\Template\Dto\TemplateDto;
use Tickets\Template\Dto\TemplateVersionDto;

class InMemoryMySqlTemplateRepository implements TemplateRepositoryInterface
{
    public function __construct(
        private TemplateModel $model,
        private TemplateVersionModel $versionModel,
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

    public function saveDraft(Uuid $id, string $draftBody): bool
    {
        return (bool) $this->model::whereId($id->value())->update(['draft_body' => $draftBody]);
    }

    public function publish(Uuid $id, string $body, ?string $authorId, ?string $comment): bool
    {
        return (bool) DB::transaction(function () use ($id, $body, $authorId, $comment) {
            $this->model::whereId($id->value())->update([
                'body' => $body,
                'draft_body' => null,
            ]);

            $this->versionModel::create([
                'id' => Uuid::random()->value(),
                'template_id' => $id->value(),
                'body' => $body,
                'comment' => $comment,
                'author_id' => $authorId,
                'created_at' => (string) Carbon::now(),
            ]);

            return true;
        });
    }

    public function getVersions(Uuid $id): Collection
    {
        return $this->versionModel::whereTemplateId($id->value())
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (TemplateVersionModel $model) => TemplateVersionDto::fromState($model->toArray()));
    }

    public function rollback(Uuid $templateId, Uuid $versionId, ?string $authorId): bool
    {
        $version = $this->versionModel::whereId($versionId->value())
            ->whereTemplateId($templateId->value())
            ->first();

        if ($version === null) {
            throw new \DomainException('Версия шаблона не найдена ' . $versionId->value());
        }

        // Откат = публикация старого body как новой версии (история append-only).
        return $this->publish($templateId, $version->body, $authorId, 'Откат к версии от ' . $version->created_at);
    }
}
