<?php

declare(strict_types=1);

namespace Tickets\BazaDelivery\Application;

use Tickets\History\Domain\ActorType;

/**
 * Контекст доставки билета в Baza для BazaDeliveryDispatcher: связь с заказом/фестивалём +
 * данные для отображения в админке (ФИО/email/номер — ПДн, admin-only) + источник/актёр для трекинга.
 *
 * Заполняется из TicketResponse (классика/qr) или AutoDto (модуль Auto) — отсюда поля
 * описательные, а не сам субъект. Зеркало EmailContext.
 */
final class BazaDeliveryContext
{
    public function __construct(
        public readonly ?string $orderId = null,
        public readonly ?string $festivalId = null,
        public readonly ?string $name = null,
        public readonly ?string $email = null,
        public readonly ?int $number = null,
        public readonly string $source = 'org_event',
        public readonly string $actorType = ActorType::SYSTEM,
    ) {
    }
}
