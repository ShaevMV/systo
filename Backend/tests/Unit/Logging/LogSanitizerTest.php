<?php

declare(strict_types=1);

namespace Tests\Unit\Logging;

use PHPUnit\Framework\TestCase;
use Shared\Infrastructure\Logging\LogSanitizer;

/**
 * Маскировка ПДн перед записью в централизованный лог (152-ФЗ, готовность к Graylog/Loki).
 */
class LogSanitizerTest extends TestCase
{
    public function test_mask_email(): void
    {
        $this->assertSame('i***@mail.ru', LogSanitizer::maskEmail('ivan@mail.ru'));
        $this->assertSame('', LogSanitizer::maskEmail(null));
        $this->assertSame('', LogSanitizer::maskEmail(''));
        $this->assertSame('***', LogSanitizer::maskEmail('notanemail'));
    }

    public function test_mask_phone_keeps_only_last_two_digits(): void
    {
        $this->assertSame('***85', LogSanitizer::maskPhone('+7 (912) 345-67-85'));
        $this->assertSame('***85', LogSanitizer::maskPhone('89123456785'));
        $this->assertSame('***', LogSanitizer::maskPhone('12'));
        $this->assertSame('', LogSanitizer::maskPhone(null));
    }

    public function test_mask_telegram(): void
    {
        $this->assertSame('@iv***', LogSanitizer::maskTelegram('@ivanov'));
        $this->assertSame('@iv***', LogSanitizer::maskTelegram('ivanov'));
        $this->assertSame('@***', LogSanitizer::maskTelegram('ab'));
        $this->assertSame('', LogSanitizer::maskTelegram(null));
    }

    public function test_sanitize_array_drops_blocklist_keys(): void
    {
        $out = LogSanitizer::sanitizeArray([
            'card_number' => '2200123412341234',
            'password' => 'secret',
            'X-QR-Token' => 'abc',
            'sql_bindings' => ['ivan@mail.ru', '79123456785'],
            'order_id' => 'uuid-123',
        ]);

        $this->assertSame('[removed]', $out['card_number']);
        $this->assertSame('[removed]', $out['password']);
        $this->assertSame('[removed]', $out['X-QR-Token']);
        $this->assertSame('[removed]', $out['sql_bindings']);
        // Неопасные поля остаются как есть.
        $this->assertSame('uuid-123', $out['order_id']);
    }

    public function test_sanitize_array_masks_known_pii_keys(): void
    {
        $out = LogSanitizer::sanitizeArray([
            'email' => 'ivan@mail.ru',
            'phone' => '+7 912 345-67-85',
            'telegram' => 'ivanov',
        ]);

        $this->assertSame('i***@mail.ru', $out['email']);
        $this->assertSame('***85', $out['phone']);
        $this->assertSame('@iv***', $out['telegram']);
    }

    public function test_sanitize_array_is_recursive(): void
    {
        $out = LogSanitizer::sanitizeArray([
            'buyer' => [
                'email' => 'ivan@mail.ru',
                'card_number' => '2200123412341234',
            ],
        ]);

        $this->assertSame('i***@mail.ru', $out['buyer']['email']);
        $this->assertSame('[removed]', $out['buyer']['card_number']);
    }

    public function test_mask_text_masks_email_and_phone_in_free_text(): void
    {
        $masked = LogSanitizer::maskText('Заказ от ivan@mail.ru, тел +7 912 345-67-85');

        $this->assertStringContainsString('i***@mail.ru', $masked);
        $this->assertStringNotContainsString('ivan@mail.ru', $masked);
        $this->assertStringNotContainsString('345-67-85', $masked);
    }
}
