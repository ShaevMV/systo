<?php

declare(strict_types=1);

namespace Tickets\Template\Application;

use Shared\Domain\Bus\Query\QueryBus;
use Shared\Domain\ValueObject\Uuid;
use Shared\Infrastructure\Bus\Query\InMemorySymfonyQueryBus;
use Tickets\Template\Application\GetList\TemplateGetListQuery;
use Tickets\Template\Application\GetList\TemplateGetListQueryHandler;
use Tickets\Template\Application\Preview\PreviewTemplateQuery;
use Tickets\Template\Application\Preview\PreviewTemplateQueryHandler;
use Tickets\Template\Dto\TemplateDto;
use Tickets\Template\Repositories\TemplateRepositoryInterface;
use Tickets\Template\Response\PreviewTemplateResponse;
use Tickets\Template\Response\TemplateGetListResponse;

/**
 * Тонкий слой над репозиторием (БД — только в репозитории, правило №1). Чтение списка идёт через
 * QueryBus (whitelist фильтров), остальное — прямые методы (паттерн QrOrderApplication).
 */
class TemplateApplication
{
    private QueryBus $queryBus;

    public function __construct(
        private readonly TemplateRepositoryInterface $repository,
        TemplateGetListQueryHandler $getListQueryHandler,
        PreviewTemplateQueryHandler $previewQueryHandler,
    ) {
        $this->queryBus = new InMemorySymfonyQueryBus([
            TemplateGetListQuery::class => $getListQueryHandler,
            PreviewTemplateQuery::class => $previewQueryHandler,
        ]);
    }

    public function getList(TemplateGetListQuery $query): TemplateGetListResponse
    {
        /** @var TemplateGetListResponse $result */
        $result = $this->queryBus->ask($query);

        return $result;
    }

    public function getPreview(PreviewTemplateQuery $query): PreviewTemplateResponse
    {
        /** @var PreviewTemplateResponse $result */
        $result = $this->queryBus->ask($query);

        return $result;
    }

    public function getItem(Uuid $id): TemplateDto
    {
        return $this->repository->getItem($id);
    }

    public function create(TemplateDto $data): bool
    {
        return $this->repository->create($data);
    }

    public function edit(Uuid $id, TemplateDto $data): bool
    {
        return $this->repository->editItem($id, $data);
    }

    public function activate(Uuid $id, bool $active): bool
    {
        return $this->repository->activate($id, $active);
    }

    public function saveDraft(Uuid $id, string $draftBody): bool
    {
        return $this->repository->saveDraft($id, $draftBody);
    }

    public function publish(Uuid $id, string $body, ?string $authorId, ?string $comment): bool
    {
        return $this->repository->publish($id, $body, $authorId, $comment);
    }

    public function getVersions(Uuid $id): \Illuminate\Support\Collection
    {
        return $this->repository->getVersions($id);
    }

    public function rollback(Uuid $templateId, Uuid $versionId, ?string $authorId): bool
    {
        return $this->repository->rollback($templateId, $versionId, $authorId);
    }

    /**
     * Палитра плейсхолдеров для редактора (без БД — фиксированный контракт).
     *
     * @return array<int, array{group: string, items: array<int, array{label: string, insert: string}>}>
     */
    public function getVariables(string $kind, string $slug): array
    {
        return \Tickets\Template\Domain\PlaceholderCatalog::variables($kind, $slug);
    }
}
