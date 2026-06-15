<?php

declare(strict_types=1);

namespace Tickets\TemplateBinding\Repositories;

use Illuminate\Support\Collection;
use Shared\Domain\ValueObject\Uuid;
use Tickets\TemplateBinding\Dto\TemplateBindingDto;

interface TemplateBindingRepositoryInterface
{
    /**
     * Активные привязки со slug'ами шаблонов (join templates) — для резолвера.
     *
     * @return TemplateBindingDto[]
     */
    public function getActiveForResolve(): array;

    /** Все привязки (для админки), со slug'ами. */
    public function getList(): Collection;

    public function getItem(Uuid $id): TemplateBindingDto;

    public function create(TemplateBindingDto $dto): bool;

    public function editItem(Uuid $id, TemplateBindingDto $dto): bool;

    public function remove(Uuid $id): bool;

    /** Есть ли уже активная дефолт-привязка (для валидации «один дефолт»). */
    public function hasActiveDefault(?string $excludeId = null): bool;
}
