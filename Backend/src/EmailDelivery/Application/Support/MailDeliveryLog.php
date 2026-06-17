<?php

declare(strict_types=1);

namespace Tickets\EmailDelivery\Application\Support;

use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;

/**
 * Логирование доставки писем: отдельный канал mail_delivery (JSON), без ПДн в открытом виде.
 * Канал описан в config/logging.php. По образцу QrOrder\...\PipelineLog.
 */
final class MailDeliveryLog
{
    public const CHANNEL = 'mail_delivery';

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
