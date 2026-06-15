<?php

declare(strict_types=1);

namespace Tickets\Template\Domain;

/**
 * Тип шаблона: письмо или PDF-билет. Вместе со slug образует ключ резолва (uq_slug_kind).
 */
final class TemplateKind
{
    public const EMAIL = 'email';
    public const PDF = 'pdf';

    /** @return string[] */
    public static function all(): array
    {
        return [self::EMAIL, self::PDF];
    }

    public static function isValid(?string $value): bool
    {
        return in_array($value, self::all(), true);
    }
}
