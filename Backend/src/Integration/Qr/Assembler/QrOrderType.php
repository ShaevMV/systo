<?php

declare(strict_types=1);

namespace Tickets\Integration\Qr\Assembler;

use Tickets\Integration\Qr\Exception\QrOrderRejectedException;

/**
 * Тип заказа из контракта qr (`order.type_order`, CONTRACT_RFC_v0.md §6.1).
 *
 * Машинные коды (НЕ русский текст): qr шлёт regular/friendly/live/list, org мапит их
 * на дискриминаторы (friendly_id / curator_id / is_live_ticket) в Ф3.
 */
final class QrOrderType
{
    public const REGULAR  = 'regular';
    public const FRIENDLY = 'friendly';
    public const LIVE     = 'live';
    public const LIST     = 'list';

    private const ALL = [self::REGULAR, self::FRIENDLY, self::LIVE, self::LIST];

    public function __construct(public readonly string $value)
    {
        if (! in_array($this->value, self::ALL, true)) {
            throw new QrOrderRejectedException(sprintf(
                'Неизвестный type_order "%s" (ожидается: %s)',
                $this->value,
                implode(', ', self::ALL),
            ));
        }
    }

    public function isList(): bool
    {
        return $this->value === self::LIST;
    }

    public function isFriendly(): bool
    {
        return $this->value === self::FRIENDLY;
    }

    public function isLive(): bool
    {
        return $this->value === self::LIVE;
    }
}
