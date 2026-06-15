<?php

declare(strict_types=1);

namespace Tickets\TemplateBinding\Application;

use Illuminate\Support\Collection;
use Shared\Domain\ValueObject\Uuid;
use Tickets\TemplateBinding\Domain\TemplateBindingResolver;
use Tickets\TemplateBinding\Dto\TemplateBindingDto;
use Tickets\TemplateBinding\Repositories\TemplateBindingRepositoryInterface;

/**
 * Тонкий слой над репозиторием привязок (БД — только в репозитории, правило №1).
 * Резолв slug'а — через чистый TemplateBindingResolver на активных привязках из БД.
 */
class TemplateBindingApplication
{
    public function __construct(
        private readonly TemplateBindingRepositoryInterface $repository,
        private readonly TemplateBindingResolver $resolver,
    ) {
    }

    public function getList(): Collection
    {
        return $this->repository->getList();
    }

    public function getItem(Uuid $id): TemplateBindingDto
    {
        return $this->repository->getItem($id);
    }

    public function create(TemplateBindingDto $dto): bool
    {
        return $this->repository->create($dto);
    }

    public function edit(Uuid $id, TemplateBindingDto $dto): bool
    {
        return $this->repository->editItem($id, $dto);
    }

    public function delete(Uuid $id): bool
    {
        return $this->repository->remove($id);
    }

    public function hasActiveDefault(?string $excludeId = null): bool
    {
        return $this->repository->hasActiveDefault($excludeId);
    }

    /**
     * Резолв slug'а шаблона по (festival, order_type, ticket_type) для нужного kind.
     * null → привязки нет, вызывающий использует старый slug (обратная совместимость).
     */
    public function resolveSlug(string $kind, ?string $festivalId, ?string $orderType, ?string $ticketTypeId): ?string
    {
        return $this->resolver->resolve(
            $this->repository->getActiveForResolve(),
            $kind,
            $festivalId,
            $orderType,
            $ticketTypeId,
        );
    }
}
