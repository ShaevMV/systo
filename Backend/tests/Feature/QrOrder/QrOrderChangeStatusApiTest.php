<?php

declare(strict_types=1);

namespace Tests\Feature\QrOrder;

use Database\Seeders\TypeTicketsSeeder;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Tickets\Order\OrderTicket\Helpers\FestivalHelper;
use Tickets\QrOrder\Application\Issuance\IssueOrderJob;

/**
 * Восстановленный S2S-эндпоинт смены статуса qr-заказа: POST /api/v1/qrOrder/changeStatus/{id}.
 * Перевод в «оплачен» запускает выдачу билетов (IssueOrderJob) один раз; канал закрыт X-QR-Token.
 */
class QrOrderChangeStatusApiTest extends TestCase
{
    use WithQrIngestToken;

    private const ORDER_ID = '12121212-1212-4121-8121-121212121212';

    protected function setUp(): void
    {
        parent::setUp();
        $this->configureQrIngestToken();
    }

    private function createOrder(): void
    {
        $contract = [
            'order_id' => self::ORDER_ID,
            'user' => ['name' => 'Иван', 'city' => 'Москва', 'phone' => '+70000000000'],
            'price' => ['total' => 4200],
            'order_data' => [
                'type_order' => 'regular',
                'festival' => ['id' => FestivalHelper::UUID_FESTIVAL, 'title' => 'Систо'],
                'status' => 'создан',
                'email' => 'buyer@example.com',
            ],
            'guests' => [
                ['name' => 'Иван Гость', 'email' => 'guest@example.com',
                 'type_ticket' => ['id' => TypeTicketsSeeder::ID_FOR_FIRST_WAVE, 'title' => 'Оргвзнос', 'options' => []]],
            ],
        ];
        $this->postJson('/api/v1/qrOrder/create', $contract, $this->qrIngestHeaders())->assertOk();
    }

    public function test_change_to_paid_dispatches_issue_job(): void
    {
        Queue::fake();
        $this->createOrder();

        $this->postJson('/api/v1/qrOrder/changeStatus/' . self::ORDER_ID,
            ['status' => 'оплачен'], $this->qrIngestHeaders())->assertOk();

        Queue::assertPushed(IssueOrderJob::class);
        // Заказ помечен выданным (issued_at != null) — защита от повторной выдачи.
        $this->assertDatabaseMissing('qr_orders', ['id' => self::ORDER_ID, 'issued_at' => null]);
    }

    public function test_requires_qr_token(): void
    {
        Queue::fake();
        $this->createOrder();

        // Без X-QR-Token middleware qr.ingest отдаёт 401.
        $this->postJson('/api/v1/qrOrder/changeStatus/' . self::ORDER_ID, ['status' => 'оплачен'])
            ->assertStatus(401);
    }

    public function test_unknown_order_returns_404(): void
    {
        $this->postJson('/api/v1/qrOrder/changeStatus/00000000-0000-4000-8000-000000000000',
            ['status' => 'оплачен'], $this->qrIngestHeaders())->assertStatus(404);
    }

    public function test_empty_status_returns_422(): void
    {
        Queue::fake();
        $this->createOrder();

        $this->postJson('/api/v1/qrOrder/changeStatus/' . self::ORDER_ID,
            ['status' => ''], $this->qrIngestHeaders())->assertStatus(422);
    }
}
