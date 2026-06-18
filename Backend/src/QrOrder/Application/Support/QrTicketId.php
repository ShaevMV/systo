<?php

declare(strict_types=1);

namespace Tickets\QrOrder\Application\Support;

use Ramsey\Uuid\Uuid as RamseyUuid;
use Shared\Domain\ValueObject\Uuid;

/**
 * Детерминированный UUID билета из (order_id, индекс гостя) — uuid5.
 *
 * Зачем: делает создание билета ИДЕМПОТЕНТНЫМ. При повторном прогоне выдачи (ретрай/переотправка
 * «оплачен» после сбоя) тот же гость даёт тот же ticket_id → шаг видит существующий билет и не
 * создаёт дубль. До этого id был random() → повтор плодил дубликаты билетов.
 */
final class QrTicketId
{
    /** Фиксированное пространство имён для билетов qr (стабильно между прогонами). */
    private const NAMESPACE = 'a3c8e1d2-5b4f-4e6a-9c7d-1f2e3a4b5c6d';

    public static function forGuest(Uuid $orderId, int $index): Uuid
    {
        return new Uuid(
            RamseyUuid::uuid5(self::NAMESPACE, $orderId->value().':'.$index)->toString(),
        );
    }
}
