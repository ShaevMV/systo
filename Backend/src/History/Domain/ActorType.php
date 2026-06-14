<?php

declare(strict_types=1);

namespace Tickets\History\Domain;

final class ActorType
{
    public const USER         = 'user';
    public const SYSTEM       = 'system';
    public const ARTISAN      = 'artisan';
    // Автоматическое одобрение заказа по заголовку AutoPayment на /api/v1/order/create
    public const AUTO_PAYMENT = 'auto_payment';
    // Заказ пришёл из внешней витрины qr.spaceofjoy.ru по подписанной шине (см. CONTRACT_RFC_v0.md).
    // actor_id пишется null — конкретного пользователя org за командой нет, источник — система qr.
    public const QR           = 'qr';
}
