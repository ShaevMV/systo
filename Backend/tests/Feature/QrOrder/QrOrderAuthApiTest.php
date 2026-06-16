<?php

declare(strict_types=1);

namespace Tests\Feature\QrOrder;

use Tests\TestCase;

/**
 * /qrOrder/create — публичный эндпоинт приёма заказов от витрины qr: авторизация отключена
 * по решению владельца (qr шлёт уже оплаченный заказ, канал защищается вне приложения —
 * сеть / shared-secret). Тест фиксирует публичность (защита от случайного возврата middleware).
 */
class QrOrderAuthApiTest extends TestCase
{
    private function contract(): array
    {
        return [
            'order_id' => '11111111-1111-1111-1111-111111111111',
            'user' => ['city' => 'Москва', 'phone' => '+70000000000'],
            'price' => ['total' => 4000],
            'order_data' => [
                'type_order' => 'regular',
                'festival' => ['id' => '55555555-5555-5555-5555-555555555555', 'title' => 'Систо'],
                'status' => 'создан',
                'email' => 'buyer@example.com',
            ],
            'guests' => [
                ['name' => 'Иван Гость', 'email' => 'guest@example.com',
                 'type_ticket' => ['id' => '44444444-4444-4444-4444-444444444444', 'title' => 'Оргвзнос', 'options' => []]],
            ],
        ];
    }

    public function test_create_is_public_no_token_required(): void
    {
        // Без токена канал открыт (не 401) — заказ принимается.
        $this->postJson('/api/v1/qrOrder/create', $this->contract())
            ->assertOk()
            ->assertJson(['success' => true]);
    }
}
