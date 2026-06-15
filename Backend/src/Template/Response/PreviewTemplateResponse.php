<?php

declare(strict_types=1);

namespace Tickets\Template\Response;

use Shared\Domain\Bus\Query\Response;

/**
 * Результат предпросмотра: отрендеренный HTML. Для kind=pdf контроллер дополнительно прогоняет
 * этот HTML через DomPDF и отдаёт PDF-поток.
 */
class PreviewTemplateResponse implements Response
{
    public function __construct(
        private string $kind,
        private string $html,
    ) {
    }

    public function getKind(): string
    {
        return $this->kind;
    }

    public function getHtml(): string
    {
        return $this->html;
    }
}
