<?php

declare(strict_types=1);

namespace Tickets\QrOrder\Application\Support;

use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;

/**
 * Логирование pipeline выдачи qr: отдельный канал qr_pipeline (JSON), без ПДн в открытом виде.
 * Канал описан в config/logging.php. См. TD-10 (единый структурированный поток логов).
 */
final class PipelineLog
{
    public const CHANNEL = 'qr_pipeline';

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

        return mb_substr($email, 0, 1).'***'.mb_substr($email, $at);
    }
}
