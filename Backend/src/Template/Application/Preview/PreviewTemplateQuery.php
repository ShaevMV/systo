<?php

declare(strict_types=1);

namespace Tickets\Template\Application\Preview;

use Shared\Domain\Bus\Query\Query;

/**
 * Запрос предпросмотра: рендерит ПЕРЕДАННОЕ тело (текущий несохранённый исходник) на тестовых
 * данных. Не трогает БД — чистый рендер. kind определяет, чем отдавать (html письма / PDF).
 */
class PreviewTemplateQuery implements Query
{
    public function __construct(
        private string $kind,
        private string $slug,
        private string $body,
    ) {
    }

    public function getKind(): string
    {
        return $this->kind;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getBody(): string
    {
        return $this->body;
    }
}
