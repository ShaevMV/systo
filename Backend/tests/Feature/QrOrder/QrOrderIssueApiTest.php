<?php

declare(strict_types=1);

namespace Tests\Feature\QrOrder;

use Database\Seeders\TypeTicketsSeeder;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Tickets\Order\OrderTicket\Helpers\FestivalHelper;
use Tickets\QrOrder\Application\Issuance\IssueOrderJob;

/**
 * Выдача при приёме оплаченного заказа (вариант «один запрос»): qr присылает заказ уже
 * со status=«оплачен» → create ставит оркестратор IssueOrderJob АСИНХРОННО (вне HTTP qr) и
 * помечает заказ issued_at. Повторный приём того же id задачу не дублирует; неоплаченный
 * статус выдачу не запускает.
 */
class QrOrderIssueApiTest extends TestCase
{
    use WithQrIngestToken;

    private const ORDER_ID = '11111111-1111-1111-1111-111111111111';

    protected function setUp(): void
    {
        parent::setUp();
        // S2S-канал закрыт сервисным ключом qr (X-QR-Token) — настраиваем валидный ключ.
        $this->configureQrIngestToken();
    }

    private function contract(string $status = 'оплачен'): array
    {
        return [
            'order_id' => self::ORDER_ID,
            'user' => ['user_id' => '22222222-2222-2222-2222-222222222222', 'name' => 'Иван', 'city' => 'Москва', 'phone' => '+70000000000'],
            'price' => ['price' => 4200, 'discount' => 0, 'total' => 4200],
            'order_data' => [
                'type_order' => 'regular',
                'festival' => ['id' => FestivalHelper::UUID_FESTIVAL, 'title' => 'Систо'],
                'types_of_payment' => ['title' => 'СБП', 'id' => '33333333-3333-3333-3333-333333333333'],
                'comment' => null,
                'status' => $status,
                'email' => 'buyer@example.com',
            ],
            'guests' => [
                ['name' => 'Иван Гость', 'email' => 'guest@example.com',
                 'type_ticket' => ['id' => TypeTicketsSeeder::ID_FOR_FIRST_WAVE, 'title' => 'Оргвзнос', 'options' => []]],
            ],
        ];
    }

    public function test_paid_status_dispatches_issue_job(): void
    {
        Queue::fake();

        // qr присылает заказ уже оплаченным → приём сразу ставит задачу выдачи.
        $this->postJson('/api/v1/qrOrder/create', $this->contract('оплачен'), $this->qrIngestHeaders())->assertOk();

        Queue::assertPushed(IssueOrderJob::class);

        // Заказ помечен выданным (issued_at != null).
        $this->assertDatabaseMissing('qr_orders', ['id' => self::ORDER_ID, 'issued_at' => null]);
    }

    public function test_issue_is_idempotent_on_repeated_paid(): void
    {
        Queue::fake();

        // Повторный приём того же оплаченного заказа (qr-ретрай) не ставит вторую задачу
        // выдачи — отсекается existsById по id (== id заказа org).
        $this->postJson('/api/v1/qrOrder/create', $this->contract('оплачен'), $this->qrIngestHeaders())->assertOk();
        $this->postJson('/api/v1/qrOrder/create', $this->contract('оплачен'), $this->qrIngestHeaders())->assertOk();

        Queue::assertPushed(IssueOrderJob::class, 1);
    }

    public function test_non_paid_status_does_not_dispatch(): void
    {
        Queue::fake();

        // Заказ в статусе «создан» (ещё не оплачен) — выдача не запускается.
        $this->postJson('/api/v1/qrOrder/create', $this->contract('создан'), $this->qrIngestHeaders())->assertOk();

        Queue::assertNotPushed(IssueOrderJob::class);
    }
}
