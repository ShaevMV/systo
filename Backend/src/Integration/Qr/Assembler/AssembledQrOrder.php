<?php

declare(strict_types=1);

namespace Tickets\Integration\Qr\Assembler;

/**
 * Заказ qr после сборки и валидации (результат {@see QrOrderAssembler}) — антикоррупционная
 * граница между контрактом qr и доменом org. Дальше Ф3 строит из него заказ через фабрики OrderTicket.
 *
 * Идентичность (friendlyId/curatorId/pusherId) берётся из ПОДПИСАННОГО payload (CONTRACT_RFC_v0.md §6.2),
 * существование UUID в org проверяется на шаге создания (Ф3). Цена — декларированная qr (Р2).
 */
final class AssembledQrOrder
{
    /**
     * @param AssembledQrGuest[] $guests
     */
    public function __construct(
        public readonly QrOrderType $type,
        public readonly string $qrOrderId,
        public readonly string $festivalId,
        public readonly string $recipientEmail,
        public readonly ?string $recipientName,
        public readonly ?string $recipientCity,
        public readonly ?string $recipientPhone,
        public readonly ?string $typesOfPaymentId,
        public readonly ?string $friendlyId,
        public readonly ?string $curatorId,
        public readonly ?string $locationId,
        public readonly ?string $comment,
        public readonly int $declaredPrice,
        public readonly int $declaredDiscount,
        public readonly int $declaredTotal,
        public readonly array $guests,
    ) {
    }
}
