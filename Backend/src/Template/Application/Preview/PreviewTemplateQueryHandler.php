<?php

declare(strict_types=1);

namespace Tickets\Template\Application\Preview;

use Shared\Domain\Bus\Query\QueryHandler;
use Tickets\Template\Domain\PlaceholderCatalog;
use Tickets\Template\Response\PreviewTemplateResponse;
use Tickets\Template\Service\TemplateRenderer;

/**
 * Рендерит переданное тело шаблона на ТЕСТОВЫХ данных (PlaceholderCatalog::sample) — без БД и ПДн.
 * Ошибки синтаксиса Mustache пробрасываются наверх → контроллер отдаёт 422 (не 500).
 */
class PreviewTemplateQueryHandler implements QueryHandler
{
    public function __construct(
        private TemplateRenderer $renderer,
    ) {
    }

    public function __invoke(PreviewTemplateQuery $query): PreviewTemplateResponse
    {
        $sample = PlaceholderCatalog::sample($query->getKind(), $query->getSlug());

        $html = $this->renderer->render($query->getBody(), $sample);

        return new PreviewTemplateResponse($query->getKind(), $html);
    }
}
