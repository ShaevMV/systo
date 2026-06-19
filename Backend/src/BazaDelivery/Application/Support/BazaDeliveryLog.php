<?php

declare(strict_types=1);

namespace Tickets\BazaDelivery\Application\Support;

use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;

/**
 * Логирование доставки билетов в Baza: отдельный канал baza_delivery (JSON), без ПДн открыто.
 * Канал описан в config/logging.php. По образцу EmailDelivery\...\MailDeliveryLog.
 */
final class BazaDeliveryLog
{
    public const CHANNEL = 'baza_delivery';

    public static function logger(): LoggerInterface
    {
        return Log::channel(self::CHANNEL);
    }

    /** Маскирует email для логов: ivan@mail.ru → i***@mail.ru (152-ФЗ: не пишем ПДн открыто). */
    public static function maskEmail(?string $email): string
    {
        if ($email === null || $email === '') {
            return '';
        }

        $at = mb_strpos($email, '@');
        if ($at === false || $at < 1) {
            return '***';
        }

        return mb_substr($email, 0, 1) . '***' . mb_substr($email, $at);
    }
}
