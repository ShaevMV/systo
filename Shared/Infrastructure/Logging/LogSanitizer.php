<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Logging;

/**
 * Маскировка / очистка ПДн перед записью в централизованный лог (152-ФЗ).
 *
 * Чистые функции без зависимостей — переиспользуются и Backend, и Baza (Shared PSR-4).
 * Применяется Monolog-процессором (App\Logging\MaskPiiProcessor) на канале `structured`,
 * который дочитывает внешний шиппер (Vector/Fluent Bit/Promtail) → Graylog/Loki.
 * См. .claude/specs/graylog-readiness.md.
 */
final class LogSanitizer
{
    /** Ключи, значения которых НИКОГДА не пишем в лог (вырезаем целиком). */
    private const BLOCK_KEYS = [
        'password', 'password_confirmation', 'current_password',
        'card_number', 'cardnumber', 'cvv', 'pan',
        'x-qr-token', 'x-baza-token', 'authorization', 'token', 'secret', 'api_key',
        'sql_bindings', 'bindings',
        'child', 'search', 'search_blob',
    ];

    /** Ключи, значения которых маскируем как email. */
    private const EMAIL_KEYS = ['email', 'recipient', 'e-mail'];

    /** Ключи — как телефон. */
    private const PHONE_KEYS = ['phone', 'tel', 'trustedphone', 'trusted_phone', 'parent_phone'];

    /** Ключи — как telegram. */
    private const TELEGRAM_KEYS = ['telegram', 'tg'];

    /** ivan@mail.ru → i***@mail.ru */
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

    /** +7 (912) 345-67-85 → ***85 (оставляем 2 последние цифры). */
    public static function maskPhone(?string $phone): string
    {
        if ($phone === null || $phone === '') {
            return '';
        }

        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if (mb_strlen($digits) < 4) {
            return '***';
        }

        return '***' . mb_substr($digits, -2);
    }

    /** @ivanov → @iv*** */
    public static function maskTelegram(?string $tg): string
    {
        if ($tg === null || $tg === '') {
            return '';
        }

        $tg = ltrim($tg, '@');
        if (mb_strlen($tg) <= 2) {
            return '@***';
        }

        return '@' . mb_substr($tg, 0, 2) . '***';
    }

    /** Маскирует email'ы и длинные цифровые последовательности (телефоны) в свободном тексте. */
    public static function maskText(string $text): string
    {
        $text = preg_replace_callback(
            '/[A-Za-z0-9._%+\-]+@[A-Za-z0-9.\-]+\.[A-Za-z]{2,}/u',
            static fn (array $m): string => self::maskEmail($m[0]),
            $text,
        ) ?? $text;

        // 7+ цифр подряд (с возможными разделителями +, -, пробел, скобки) → телефон.
        $text = preg_replace_callback(
            '/\+?\d[\d\-\s()]{6,}\d/u',
            static fn (array $m): string => self::maskPhone($m[0]),
            $text,
        ) ?? $text;

        return $text;
    }

    /**
     * Рекурсивно: вырезает blocklist-ключи, маскирует known-ПДн-ключи (email/phone/telegram),
     * прочие строковые значения прогоняет через maskText (на случай ПДн в произвольном поле).
     */
    public static function sanitizeArray(array $data): array
    {
        $out = [];

        foreach ($data as $key => $value) {
            $lowerKey = is_string($key) ? mb_strtolower($key) : null;

            if ($lowerKey !== null && in_array($lowerKey, self::BLOCK_KEYS, true)) {
                $out[$key] = '[removed]';
                continue;
            }

            if (is_array($value)) {
                $out[$key] = self::sanitizeArray($value);
                continue;
            }

            if (is_string($value)) {
                if ($lowerKey !== null && in_array($lowerKey, self::EMAIL_KEYS, true)) {
                    $out[$key] = self::maskEmail($value);
                    continue;
                }
                if ($lowerKey !== null && in_array($lowerKey, self::PHONE_KEYS, true)) {
                    $out[$key] = self::maskPhone($value);
                    continue;
                }
                if ($lowerKey !== null && in_array($lowerKey, self::TELEGRAM_KEYS, true)) {
                    $out[$key] = self::maskTelegram($value);
                    continue;
                }

                $out[$key] = self::maskText($value);
                continue;
            }

            $out[$key] = $value;
        }

        return $out;
    }
}
