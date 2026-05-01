<?php

declare(strict_types=1);

namespace Tickets\Orders\Shared\Domain\ValueObject;

use InvalidArgumentException;
use Shared\Domain\ValueObject\Enum;

/**
 * Тип заказа — определяет способ создания и набор бизнес-правил.
 *
 * Каждый тип имеет свой kilter-префикс для формирования номера заказа.
 */
final class OrderType extends Enum
{
    public const GUEST       = 'guest';       // Гостевой: покупка через форму, привязан к ЛК
    public const FRIENDLY    = 'friendly';    // Дружеский: создаётся пушером, без ЛК
    public const LIVE        = 'live';        // Живой: карточка live-билета с номером
    public const FOREST_CARD = 'forest_card'; // Лесная карта: доступ к 2 фестивалям
    public const LIST        = 'list';        // Списки: от куратора, вместо типов билетов — локации
    public const PARKING     = 'parking';     // Парковка: привязан к существующему заказу

    private const KILTER_PREFIXES = [
        self::GUEST       => 'G',
        self::FRIENDLY    => 'F',
        self::LIVE        => 'L',
        self::FOREST_CARD => 'LC',
        self::LIST        => 'S',
        self::PARKING     => 'P',
    ];

    public function getKilterPrefix(): string
    {
        return self::KILTER_PREFIXES[$this->value];
    }

    /** Форматирует килтер в читаемый номер заказа, например G-1042. */
    public function formatKilter(int $kilter): string
    {
        return $this->getKilterPrefix() . '-' . $kilter;
    }

    protected function throwExceptionForInvalidValue($value): void
    {
        throw new InvalidArgumentException(
            sprintf('<%s> does not allow the value <%s>.', self::class, $value)
        );
    }
}
