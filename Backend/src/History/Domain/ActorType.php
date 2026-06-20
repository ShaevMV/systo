<?php

declare(strict_types=1);

namespace Tickets\History\Domain;

final class ActorType
{
    public const USER = 'user';

    public const SYSTEM = 'system';

    public const ARTISAN = 'artisan';

    // Автоматическое одобрение заказа по заголовку AutoPayment на /api/v1/order/create
    public const AUTO_PAYMENT = 'auto_payment';

    // Действия по заказам, пришедшим от витрины qr (S2S-канал, не человек)
    public const QR = 'qr';

    // События от Baza (системы входа): S2S-канал, не человек (вебхук «билет прошёл» Ф4 и пр.)
    public const BAZA = 'baza';
}
