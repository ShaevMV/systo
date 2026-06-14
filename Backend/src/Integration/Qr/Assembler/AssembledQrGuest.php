<?php

declare(strict_types=1);

namespace Tickets\Integration\Qr\Assembler;

use Tickets\Order\OrderTicket\Application\Pricing\Dto\RawGuestOptionInput;

/**
 * Один гость заказа qr после сборки и валидации (результат {@see QrOrderAssembler}).
 *
 * Цена — ДЕКЛАРИРОВАННАЯ витриной qr (Р2: qr — мастер цены, см. CONTRACT_RFC_v0.md §5.2).
 * Хранятся целые рубли (как Money VO). Для заказов-списков цена = 0, ticketTypeId = null.
 * Опции — переиспользуем {@see RawGuestOptionInput} (option_id + qty, MAX_QTY).
 */
final class AssembledQrGuest
{
    /**
     * @param RawGuestOptionInput[] $options
     */
    public function __construct(
        public readonly string $value,
        public readonly string $email,
        public readonly ?string $ticketTypeId,
        public readonly array $options,
        public readonly ?string $promoCode,
        public readonly ?string $liveNumber,
        public readonly int $declaredBasePrice,
        public readonly int $declaredOptionsSum,
        public readonly int $declaredDiscount,
        public readonly int $declaredTotal,
    ) {
    }
}
