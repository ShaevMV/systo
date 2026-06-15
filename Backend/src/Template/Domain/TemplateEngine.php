<?php

declare(strict_types=1);

namespace Tickets\Template\Domain;

/**
 * Движок тела шаблона:
 *  - html — исходник как есть (Mustache-рендер по плейсхолдерам);
 *  - mjml — MJML компилируется в HTML на сохранении (только для писем), результат в compiled_html.
 *
 * В обоих случаях плейсхолдеры — Mustache (logic-less, без исполнения PHP).
 */
final class TemplateEngine
{
    public const HTML = 'html';
    public const MJML = 'mjml';

    /** @return string[] */
    public static function all(): array
    {
        return [self::HTML, self::MJML];
    }

    public static function isValid(?string $value): bool
    {
        return in_array($value, self::all(), true);
    }
}
