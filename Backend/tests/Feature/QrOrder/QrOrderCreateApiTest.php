<?php

declare(strict_types=1);

namespace Tests\Feature\QrOrder;

use App\Models\QrOrder\QrOrderModel;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * API №1 — приём заказа от витрины qr (POST /api/v1/qrOrder/create):
 * расширенный JSON сохраняется в qr_orders (проекция для фильтров + payload as-is),
 * приём идемпотентен по id, обязательные поля валидируются.
 */
class QrOrderCreateApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // S2S-канал защищён: аутентифицируем сервис-токеном со scope qr:ingest.
        Sanctum::actingAs(User::factory()->create(), ['qr:ingest']);
        // Контракт приходит оплаченным → приём запускает выдачу; в тестах приёма её фейкаем.
        Queue::fake();
    }

    private function contract(string $orderId = '11111111-1111-1111-1111-111111111111'): array
    {
        return [
            'order_id' => $orderId,
            'user' => ['user_id' => '22222222-2222-2222-2222-222222222222', 'name' => 'Иван', 'city' => 'Москва', 'phone' => '+70000000000'],
            'price' => ['price' => 4200, 'discount' => 200, 'total' => 4000],
            'order_data' => [
                'type_order' => 'regular',
                'festival' => ['id' => '55555555-5555-5555-5555-555555555555', 'title' => 'Систо 2026'],
                'types_of_payment' => ['title' => 'СБП', 'id' => '33333333-3333-3333-3333-333333333333'],
                'comment' => 'коммент',
                'status' => 'оплачен',
                'email' => 'buyer@example.com',
            ],
            'guests' => [
                ['name' => 'Иван Гость', 'email' => 'guest@example.com', 'promocode' => 'SUMMER',
                 'type_ticket' => ['id' => '44444444-4444-4444-4444-444444444444', 'title' => 'Оргвзнос', 'options' => []]],
            ],
        ];
    }

    public function test_creates_qr_order_from_extended_json(): void
    {
        // Расширенный JSON → строка в qr_orders с заполненной проекцией под фильтры.
        $response = $this->postJson('/api/v1/qrOrder/create', $this->contract());

        $response->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseHas('qr_orders', [
            'id' => '11111111-1111-1111-1111-111111111111',
            'email' => 'buyer@example.com',
            'status' => 'оплачен',
            'type_order' => 'regular',
            'festival_id' => '55555555-5555-5555-5555-555555555555',
            'city' => 'Москва',
            'total_price' => 4000,
        ]);

        // payload сохранён целиком (гости доступны для последующей выдачи билетов).
        $row = QrOrderModel::whereId('11111111-1111-1111-1111-111111111111')->firstOrFail();
        self::assertCount(1, $row->payload['guests']);
        self::assertSame('Иван Гость', $row->payload['guests'][0]['name']);
    }

    public function test_create_is_idempotent_by_order_id(): void
    {
        // Повторный приём того же заказа (id == id заказа org) не создаёт дубль.
        $this->postJson('/api/v1/qrOrder/create', $this->contract())->assertOk();
        $this->postJson('/api/v1/qrOrder/create', $this->contract())->assertOk();

        self::assertSame(1, QrOrderModel::whereId('11111111-1111-1111-1111-111111111111')->count());
    }

    public function test_rejects_contract_without_email(): void
    {
        // Нет email — некуда слать билеты → 422, заказ не создаётся.
        $contract = $this->contract();
        unset($contract['order_data']['email']);

        $this->postJson('/api/v1/qrOrder/create', $contract)->assertStatus(422);
        $this->assertDatabaseMissing('qr_orders', ['id' => '11111111-1111-1111-1111-111111111111']);
    }

    public function test_create_records_history(): void
    {
        // История пишется с actor=qr: событие created при приёме заказа.
        $this->postJson('/api/v1/qrOrder/create', $this->contract())->assertOk();

        $this->assertDatabaseHas('domain_history', [
            'aggregate_id' => '11111111-1111-1111-1111-111111111111',
            'aggregate_type' => 'qr_order',
            'event_name' => 'created',
            'actor_type' => 'qr',
        ]);
    }
}
