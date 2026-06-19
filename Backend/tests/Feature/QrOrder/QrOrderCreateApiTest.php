<?php

declare(strict_types=1);

namespace Tests\Feature\QrOrder;

use App\Models\QrOrder\QrOrderModel;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * API №1 — приём заказа от витрины qr (POST /api/v1/qrOrder/create):
 * расширенный JSON сохраняется в qr_orders (проекция для фильтров + payload as-is),
 * приём идемпотентен по id, обязательные поля валидируются.
 */
class QrOrderCreateApiTest extends TestCase
{
    use WithQrIngestToken;

    protected function setUp(): void
    {
        parent::setUp();
        // S2S-канал закрыт сервисным ключом qr (X-QR-Token) — настраиваем валидный ключ.
        $this->configureQrIngestToken();
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
        $response = $this->postJson('/api/v1/qrOrder/create', $this->contract(), $this->qrIngestHeaders());

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
        $this->postJson('/api/v1/qrOrder/create', $this->contract(), $this->qrIngestHeaders())->assertOk();
        $this->postJson('/api/v1/qrOrder/create', $this->contract(), $this->qrIngestHeaders())->assertOk();

        self::assertSame(1, QrOrderModel::whereId('11111111-1111-1111-1111-111111111111')->count());
    }

    public function test_rejects_contract_without_email(): void
    {
        // Нет email — некуда слать билеты → 422, заказ не создаётся.
        $contract = $this->contract();
        unset($contract['order_data']['email']);

        $this->postJson('/api/v1/qrOrder/create', $contract, $this->qrIngestHeaders())->assertStatus(422);
        $this->assertDatabaseMissing('qr_orders', ['id' => '11111111-1111-1111-1111-111111111111']);
    }

    public function test_create_records_history(): void
    {
        // История пишется с actor=qr: событие created при приёме заказа.
        $this->postJson('/api/v1/qrOrder/create', $this->contract(), $this->qrIngestHeaders())->assertOk();

        $this->assertDatabaseHas('domain_history', [
            'aggregate_id' => '11111111-1111-1111-1111-111111111111',
            'aggregate_type' => 'qr_order',
            'event_name' => 'created',
            'actor_type' => 'qr',
        ]);
    }

    /** Новый полный контракт qr: buyer{}, payment.amount_total, per-guest type_ticket, ПДн. */
    private function fullContract(): array
    {
        return [
            'order_id' => '99999999-9999-4999-8999-999999999999',
            'source' => 'qr.spaceofjoy.ru',
            'external_order_no' => 123,
            'order_data' => [
                'status' => 'оплачен',
                'type_order' => 'regular',
                'email' => 'ivan@example.com',
                'festival' => ['id' => '55555555-5555-5555-5555-555555555555', 'title' => 'СИСТО ОСЕНЬ'],
                'paid_at' => '2026-06-18T12:45:00+03:00',
            ],
            'payment' => [
                'method' => 'transfer',
                'amount_total' => 12300,
                'promo_codes' => ['OSEN.BUDET'],
                // ПДн/PCI — должны остаться ТОЛЬКО в payload, не в колонках.
                'method_details' => ['card_number' => '410011904396730'],
            ],
            'buyer' => [
                'user_id' => '9d6e7c10-1111-4222-8333-444455556666',
                'fio' => 'Иван Петров', 'city' => 'Казань', 'phone' => '+79991234567', 'telegram' => '@ivan',
            ],
            'guests' => [
                ['role' => 'org_fee', 'name' => 'Иван Петров',
                 'type_ticket' => ['id' => '44444444-4444-4444-4444-444444444444', 'title' => 'Оргвзнос']],
                ['role' => 'kid', 'name' => 'Маша Петрова (7 лет)',
                 'type_ticket' => ['id' => 'c3d4e5f6-aaaa-4bbb-8ccc-dddd11112222', 'title' => 'Детский'],
                 'child' => ['name' => 'Маша Петрова', 'age' => 7, 'allergies' => 'пыльца']],
                ['role' => 'eco_car', 'name' => 'А123БВ77 / авто / Иван',
                 'type_ticket' => ['id' => '20066a25-eeee-4fff-8000-111122223333', 'title' => 'Парковка'],
                 'car' => ['number' => 'А123БВ77', 'driver_fio' => 'Иван Петров']],
            ],
        ];
    }

    public function test_creates_from_new_full_contract(): void
    {
        $this->postJson('/api/v1/qrOrder/create', $this->fullContract(), $this->qrIngestHeaders())
            ->assertOk()->assertJson(['success' => true]);

        // Проекция: buyer_fio/festival_title (новые) + total_price из payment.amount_total.
        $this->assertDatabaseHas('qr_orders', [
            'id' => '99999999-9999-4999-8999-999999999999',
            'email' => 'ivan@example.com',
            'status' => 'оплачен',
            'festival_id' => '55555555-5555-5555-5555-555555555555',
            'festival_title' => 'СИСТО ОСЕНЬ',
            'buyer_fio' => 'Иван Петров',
            'city' => 'Казань',
            'total_price' => 12300,
            'payment_method' => 'transfer',
            'promo_code' => 'OSEN.BUDET',
            'external_order_no' => '123',
        ]);

        // payload хранит ВЕСЬ контракт as-is (3 гостя, ПДн ребёнка/карты — только тут).
        $row = QrOrderModel::whereId('99999999-9999-4999-8999-999999999999')->firstOrFail();
        self::assertCount(3, $row->payload['guests']);
        self::assertSame(7, $row->payload['guests'][1]['child']['age']);
        self::assertSame('410011904396730', $row->payload['payment']['method_details']['card_number']);
        // ПДн ребёнка/карты НЕ спроецированы в колонки (минимизация по 152-ФЗ/PCI).
        self::assertArrayNotHasKey('child', $row->getAttributes());
        self::assertArrayNotHasKey('card_number', $row->getAttributes());
    }

    public function test_backward_compat_buyer_fio_from_legacy_user(): void
    {
        // Старый контракт без buyer{}: buyer_fio из user.name, city из user.city, total из price.total.
        $this->postJson('/api/v1/qrOrder/create', $this->contract(), $this->qrIngestHeaders())->assertOk();

        $this->assertDatabaseHas('qr_orders', [
            'id' => '11111111-1111-1111-1111-111111111111',
            'buyer_fio' => 'Иван',
            'city' => 'Москва',
            'total_price' => 4000,
            'festival_title' => 'Систо 2026',
        ]);
    }
}
