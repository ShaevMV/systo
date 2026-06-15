<?php

declare(strict_types=1);

namespace Tickets\Template\Repositories;

use Illuminate\Support\Collection;
use Shared\Domain\Criteria\Filters;
use Shared\Domain\Criteria\Order;
use Shared\Domain\ValueObject\Uuid;
use Tickets\Template\Dto\TemplateDto;

interface TemplateRepositoryInterface
{
    /**
     * Активный шаблон для рендера по (slug, kind). null → нет записи/неактивна → fallback на blade.
     * Точка резолва для CreatingQrCodeService (PDF) и Mailable (письма).
     */
    public function findActive(string $slug, string $kind): ?TemplateDto;

    /** Шаблон по (slug, kind) независимо от active. Для идемпотентного импорта blade. */
    public function findBySlugKind(string $slug, string $kind): ?TemplateDto;

    /** @return Collection<int, TemplateDto> Список для админки (фильтры + сортировка). */
    public function getList(Filters $filters, Order $orderBy): Collection;

    public function getItem(Uuid $id): TemplateDto;

    public function create(TemplateDto $data): bool;

    public function editItem(Uuid $id, TemplateDto $data): bool;

    /** Включить/выключить шаблон (active). Деактивация = откат на blade-fallback. */
    public function activate(Uuid $id, bool $active): bool;
}
