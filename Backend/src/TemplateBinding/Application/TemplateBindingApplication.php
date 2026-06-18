<?php

declare(strict_types=1);

namespace Tickets\TemplateBinding\Application;

use DomainException;
use Illuminate\Support\Collection;
use Shared\Domain\ValueObject\Uuid;
use Tickets\Template\Domain\TemplateKind;
use Tickets\Template\Repositories\TemplateRepositoryInterface;
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
        private readonly TemplateRepositoryInterface $templateRepository,
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
     * Кросс-проверка типа шаблона: email_template_id обязан ссылаться на email-шаблон,
     * pdf_template_id — на pdf. Возвращает текст ошибки или null (валидно).
     */
    public function templateKindError(?string $emailTemplateId, ?string $pdfTemplateId): ?string
    {
        $checks = [
            [$emailTemplateId, TemplateKind::EMAIL, 'письма'],
            [$pdfTemplateId, TemplateKind::PDF, 'PDF-билета'],
        ];

        foreach ($checks as [$id, $expectedKind, $label]) {
            if (empty($id)) {
                continue;
            }

            try {
                $template = $this->templateRepository->getItem(new Uuid($id));
            } catch (DomainException) {
                return "Шаблон {$label} не найден";
            }

            if ($template->getKind() !== $expectedKind) {
                return "Выбранный шаблон {$label} имеет другой тип ({$template->getKind()})";
            }
        }

        return null;
    }

    /**
     * Резолв slug'а шаблона по (event, festival, order_type, ticket_type) для нужного kind.
     * null → привязки нет, вызывающий использует старый slug (обратная совместимость).
     * $event = null → событие не учитывается (подходят только привязки с event = null).
     */
    public function resolveSlug(
        string $kind,
        ?string $event,
        ?string $festivalId,
        ?string $orderType,
        ?string $ticketTypeId,
        ?string $typesOfPaymentId = null,
    ): ?string {
        return $this->resolver->resolve(
            $this->repository->getActiveForResolve(),
            $kind,
            $event,
            $festivalId,
            $orderType,
            $ticketTypeId,
            $typesOfPaymentId,
        );
    }
}
