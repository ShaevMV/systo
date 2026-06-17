<?php

declare(strict_types=1);

namespace Tests\Feature\QrOrder;

use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Аутентификация S2S-канала приёма заказов qr→org (POST /api/v1/qrOrder/create):
 * канал закрыт сервисным ключом qr (заголовок X-QR-Token, middleware qr.ingest).
 * Без валидного ключа — 401, заказ не принимается. Список ключей даёт ротацию без простоя.
 */
class QrOrderAuthApiTest extends TestCase
{
    use WithQrIngestToken;

    protected function setUp(): void
    {
        parent::setUp();
        // Контракт приходит оплаченным → приём запускает выдачу; в тестах приёма её фейкаем.
        Queue::fake();
    }

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

    public function test_rejects_request_without_token(): void
    {
        $this->configureQrIngestToken();

        // Нет заголовка X-QR-Token → 401, заказ не создаётся.
        $this->postJson('/api/v1/qrOrder/create', $this->contract())
            ->assertStatus(401)
            ->assertJson(['success' => false]);

        $this->assertDatabaseMissing('qr_orders', ['id' => '11111111-1111-1111-1111-111111111111']);
    }

    public function test_rejects_request_with_wrong_token(): void
    {
        $this->configureQrIngestToken();

        // Неверный ключ → 401.
        $this->postJson('/api/v1/qrOrder/create', $this->contract(), ['X-QR-Token' => 'wrong-key'])
            ->assertStatus(401)
            ->assertJson(['success' => false]);

        $this->assertDatabaseMissing('qr_orders', ['id' => '11111111-1111-1111-1111-111111111111']);
    }

    public function test_accepts_request_with_valid_token(): void
    {
        $this->configureQrIngestToken();

        // Верный ключ → заказ принимается (200).
        $this->postJson('/api/v1/qrOrder/create', $this->contract(), $this->qrIngestHeaders())
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('qr_orders', ['id' => '11111111-1111-1111-1111-111111111111']);
    }

    public function test_accepts_any_token_during_rotation(): void
    {
        // Ротация без простоя: в конфиге два валидных ключа (старый + новый) одновременно.
        config(['services.qr_ingest.tokens' => ['old-key', 'new-key']]);

        // Старый ключ ещё валиден.
        $this->postJson('/api/v1/qrOrder/create', $this->contract(), ['X-QR-Token' => 'old-key'])
            ->assertOk();

        // Новый ключ тоже валиден.
        $this->postJson('/api/v1/qrOrder/create', $this->contract(), ['X-QR-Token' => 'new-key'])
            ->assertOk();
    }

    public function test_channel_is_closed_when_no_tokens_configured(): void
    {
        // Безопасный дефолт: ключи не сконфигурированы → канал закрыт даже с любым заголовком.
        config(['services.qr_ingest.tokens' => []]);

        $this->postJson('/api/v1/qrOrder/create', $this->contract(), ['X-QR-Token' => 'anything'])
            ->assertStatus(401);

        $this->assertDatabaseMissing('qr_orders', ['id' => '11111111-1111-1111-1111-111111111111']);
    }
}
