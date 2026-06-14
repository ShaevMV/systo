<?php

declare(strict_types=1);

namespace Tests\Feature\QrOrder;

use App\Models\User;
use Database\Seeders\TypeTicketsSeeder;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Tickets\Order\OrderTicket\Helpers\FestivalHelper;
use Tickets\QrOrder\Application\Issuance\IssueOrderJob;

/**
 * API №2b — при переходе заказа в «оплачен» выдача запускается АСИНХРОННО:
 * ставится оркестратор IssueOrderJob (вне HTTP-запроса qr), заказ помечается issued_at,
 * повторный «оплачен» не ставит задачу снова, неоплаченный статус не запускает выдачу.
 */
class QrOrderIssueApiTest extends TestCase
{
    private const ORDER_ID = '11111111-1111-1111-1111-111111111111';

    protected function setUp(): void
    {
        parent::setUp();
        // S2S-канал защищён: аутентифицируем сервис-токеном со scope qr:ingest.
        Sanctum::actingAs(User::factory()->create(), ['qr:ingest']);
    }

    private function contract(): array
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
                'status' => 'создан',
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

        // Принимаем заказ, затем переводим в «оплачен» → должна встать задача выдачи.
        $this->postJson('/api/v1/qrOrder/create', $this->contract())->assertOk();
        $this->postJson('/api/v1/qrOrder/changeStatus/' . self::ORDER_ID, ['status' => 'оплачен'])->assertOk();

        Queue::assertPushed(IssueOrderJob::class);

        // Заказ помечен выданным (issued_at != null).
        $this->assertDatabaseMissing('qr_orders', ['id' => self::ORDER_ID, 'issued_at' => null]);
    }

    public function test_issue_is_idempotent_on_repeated_paid(): void
    {
        Queue::fake();

        $this->postJson('/api/v1/qrOrder/create', $this->contract())->assertOk();
        $this->postJson('/api/v1/qrOrder/changeStatus/' . self::ORDER_ID, ['status' => 'оплачен'])->assertOk();
        // Повторный «оплачен» не должен поставить вторую задачу выдачи (защита по issued_at).
        $this->postJson('/api/v1/qrOrder/changeStatus/' . self::ORDER_ID, ['status' => 'оплачен'])->assertOk();

        Queue::assertPushed(IssueOrderJob::class, 1);
    }

    public function test_non_paid_status_does_not_dispatch(): void
    {
        Queue::fake();

        $this->postJson('/api/v1/qrOrder/create', $this->contract())->assertOk();
        // Статус «отменён» не запускает выдачу.
        $this->postJson('/api/v1/qrOrder/changeStatus/' . self::ORDER_ID, ['status' => 'отменён'])->assertOk();

        Queue::assertNotPushed(IssueOrderJob::class);
    }
}
