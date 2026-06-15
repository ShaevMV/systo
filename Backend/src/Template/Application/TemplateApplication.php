<?php

declare(strict_types=1);

namespace Tickets\Template\Application;

use Shared\Domain\Bus\Query\QueryBus;
use Shared\Domain\ValueObject\Uuid;
use Shared\Infrastructure\Bus\Query\InMemorySymfonyQueryBus;
use Tickets\History\Domain\ActorType;
use Tickets\History\Domain\HistoryEventInterface;
use Tickets\History\Dto\DomainHistoryDto;
use Tickets\History\Dto\SaveHistoryDto;
use Tickets\History\Repositories\HistoryRepositoryInterface;
use Tickets\Template\Application\GetList\TemplateGetListQuery;
use Tickets\Template\Application\GetList\TemplateGetListQueryHandler;
use Tickets\Template\Application\Preview\PreviewTemplateQuery;
use Tickets\Template\Application\Preview\PreviewTemplateQueryHandler;
use Tickets\Template\Domain\Template;
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
        private readonly HistoryRepositoryInterface $historyRepository,
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

    public function create(TemplateDto $data, ?string $actorId = null): bool
    {
        $ok = $this->repository->create($data);

        $template = Template::created($data->getId(), $data->getSlug(), $data->getKind(), $data->getTitle());
        $this->saveHistory($data->getId()->value(), $template->pullHistoryEvents(), $actorId);

        return $ok;
    }

    public function edit(Uuid $id, TemplateDto $data, ?string $actorId = null): bool
    {
        // Снимок ДО — чтобы записать в историю именно изменившиеся поля (без самих тел).
        $before = $this->repository->getItem($id)->toArray();
        $ok = $this->repository->editItem($id, $data);

        $after = $data->toArray();
        $changed = [];
        foreach (['title', 'slug', 'kind', 'engine', 'body'] as $field) {
            if (($before[$field] ?? null) !== ($after[$field] ?? null)) {
                $changed[] = $field;
            }
        }

        $template = Template::existing($id);
        $template->edited($changed); // пусто → история не пишется
        $this->saveHistory($id->value(), $template->pullHistoryEvents(), $actorId);

        return $ok;
    }

    public function activate(Uuid $id, bool $active, ?string $actorId = null): bool
    {
        $ok = $this->repository->activate($id, $active);

        $template = Template::existing($id);
        $template->activated($active);
        $this->saveHistory($id->value(), $template->pullHistoryEvents(), $actorId);

        return $ok;
    }

    public function saveDraft(Uuid $id, string $draftBody): bool
    {
        return $this->repository->saveDraft($id, $draftBody);
    }

    public function publish(Uuid $id, string $body, ?string $authorId, ?string $comment): bool
    {
        $ok = $this->repository->publish($id, $body, $authorId, $comment);

        $template = Template::existing($id);
        $template->published($comment);
        $this->saveHistory($id->value(), $template->pullHistoryEvents(), $authorId);

        return $ok;
    }

    public function getVersions(Uuid $id): \Illuminate\Support\Collection
    {
        return $this->repository->getVersions($id);
    }

    public function rollback(Uuid $templateId, Uuid $versionId, ?string $authorId): bool
    {
        $ok = $this->repository->rollback($templateId, $versionId, $authorId);

        $template = Template::existing($templateId);
        $template->rolledBack($versionId->value(), null);
        $this->saveHistory($templateId->value(), $template->pullHistoryEvents(), $authorId);

        return $ok;
    }

    /**
     * История изменений шаблона из domain_history (по возрастанию occurred_at).
     *
     * @return DomainHistoryDto[]
     */
    public function getHistory(Uuid $id): array
    {
        return $this->historyRepository->getByAggregateId($id->value());
    }

    /**
     * Сохранить накопленные события истории шаблона. Actor — админ (Auth::id()), тип USER.
     * artisan-команды (import-blade/sync-converted) идут мимо Application и историю НЕ пишут
     * (иначе спам на десятки строк за один импорт).
     *
     * @param array<int, HistoryEventInterface> $events
     */
    private function saveHistory(string $aggregateId, array $events, ?string $actorId): void
    {
        foreach ($events as $event) {
            $this->historyRepository->save(new SaveHistoryDto($aggregateId, $event, $actorId, ActorType::USER));
        }
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
