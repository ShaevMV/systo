<?php

declare(strict_types=1);

namespace Tests\Feature\QrOrder;

use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Защита S2S-канала qr→org: эндпоинты /qrOrder/create и /qrOrder/changeStatus
 * закрыты Sanctum-токеном со scope (ability) "qr:ingest".
 *  - без токена          → 401
 *  - токен без scope      → 403
 *  - токен с qr:ingest    → доступ есть (бизнес-логика отрабатывает)
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

    public function test_create_requires_token(): void
    {
        // Без аутентификации канал закрыт.
        $this->postJson('/api/v1/qrOrder/create', $this->contract())->assertStatus(401);
    }

    public function test_change_status_requires_token(): void
    {
        $this->postJson('/api/v1/qrOrder/changeStatus/11111111-1111-1111-1111-111111111111', ['status' => 'оплачен'])
            ->assertStatus(401);
    }

    public function test_create_rejects_token_without_ability(): void
    {
        // Валидный Sanctum-токен, но без scope qr:ingest → доступ запрещён (least privilege).
        Sanctum::actingAs(User::factory()->create(), ['some:other']);

        $this->postJson('/api/v1/qrOrder/create', $this->contract())->assertStatus(403);
    }

    public function test_create_accepts_token_with_ability(): void
    {
        // Токен со scope qr:ingest → канал открыт, заказ принят.
        Sanctum::actingAs(User::factory()->create(), ['qr:ingest']);

        $this->postJson('/api/v1/qrOrder/create', $this->contract())
            ->assertOk()
            ->assertJson(['success' => true]);
    }
}
