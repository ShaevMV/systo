<?php

declare(strict_types=1);

namespace Tickets\Template\Domain;

/**
 * Движок тела шаблона:
 *  - html — исходник как есть (Mustache-рендер по плейсхолдерам);
 *  - mjml — БУДУЩАЯ работа: компиляция MJML→HTML на сохранении пока НЕ реализована, поэтому
 *    из allowed-набора исключён (иначе активный mjml-шаблон ушёл бы как сырой MJML = битое письмо).
 *
 * Плейсхолдеры в любом случае — Mustache (logic-less, без исполнения PHP).
 */
final class TemplateEngine
{
    public const HTML = 'html';
    public const MJML = 'mjml';

    /** Разрешённые движки. MJML добавится сюда, когда появится компиляция. */
    public static function all(): array
    {
        return [self::HTML];
    }

    public static function isValid(?string $value): bool
    {
        return in_array($value, self::all(), true);
    }
}
