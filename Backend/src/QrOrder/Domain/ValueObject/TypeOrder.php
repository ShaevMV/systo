<?php

declare(strict_types=1);

namespace Tickets\QrOrder\Domain\ValueObject;

/**
 * Тип заказа из контракта qr (order_data.type_order). Определяет стратегию выдачи билета.
 *
 * Контракт (английские строки): regular / friendly / list / live.
 * Неизвестный/пустой тип → fallback на REGULAR в реестре стратегий.
 */
final class TypeOrder
{
    public const REGULAR = 'regular';
    public const FRIENDLY = 'friendly';
    public const LIST = 'list';
    public const LIVE = 'live';

    /** Нормализует значение из контракта для сопоставления со стратегией в реестре. */
    public static function normalize(?string $value): string
    {
        return $value !== null ? mb_strtolower(trim($value)) : '';
    }
}
