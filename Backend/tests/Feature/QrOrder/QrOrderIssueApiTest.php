<?php

declare(strict_types=1);

namespace Tests\Feature\QrOrder;

use App\Mail\OrderToPaid;
use App\Models\Tickets\TicketModel;
use App\Models\User;
use Database\Seeders\TypeTicketsSeeder;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Tickets\Order\OrderTicket\Helpers\FestivalHelper;
use Tickets\Ticket\CreateTickets\Domain\ProcessCreatingQRCode;

/**
 * API №2b — выдача билетов при переходе заказа в «оплачен» (автономно по qr_orders):
 * на каждого гостя создаётся билет в tickets (order_ticket_id == id qr-заказа),
 * ставится PDF/QR в очередь и отправляется письмо с билетами; повторный «оплачен» не дублирует.
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
                'type_order' => 'обычный',
                // фестиваль и тип билета — сидерные, чтобы билет корректно создался и нашёлся шаблон
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

    public function test_paid_status_issues_ticket_and_sends_email(): void
    {
        Mail::fake();
        Queue::fake();

        // Принимаем заказ, затем переводим в «оплачен» → должна сработать выдача.
        $this->postJson('/api/v1/qrOrder/create', $this->contract())->assertOk();
        $this->postJson('/api/v1/qrOrder/changeStatus/' . self::ORDER_ID, ['status' => 'оплачен'])->assertOk();

        // Билет создан и привязан к заказу qr (order_ticket_id == id qr-заказа).
        $this->assertDatabaseHas('tickets', [
            'order_ticket_id' => self::ORDER_ID,
            'name' => 'Иван Гость',
        ]);
        self::assertSame(1, TicketModel::where('order_ticket_id', self::ORDER_ID)->count());

        // PDF/QR поставлен в очередь, письмо с билетами отправлено.
        Queue::assertPushed(ProcessCreatingQRCode::class);
        Mail::assertSent(OrderToPaid::class);

        // Заказ помечен выданным.
        $this->assertDatabaseMissing('qr_orders', ['id' => self::ORDER_ID, 'issued_at' => null]);
    }

    public function test_issue_is_idempotent_on_repeated_paid(): void
    {
        Mail::fake();
        Queue::fake();

        $this->postJson('/api/v1/qrOrder/create', $this->contract())->assertOk();
        $this->postJson('/api/v1/qrOrder/changeStatus/' . self::ORDER_ID, ['status' => 'оплачен'])->assertOk();
        // Повторный «оплачен» не должен выдать билеты снова (защита по issued_at).
        $this->postJson('/api/v1/qrOrder/changeStatus/' . self::ORDER_ID, ['status' => 'оплачен'])->assertOk();

        self::assertSame(1, TicketModel::where('order_ticket_id', self::ORDER_ID)->count());
    }

    public function test_non_paid_status_does_not_issue(): void
    {
        Mail::fake();
        Queue::fake();

        $this->postJson('/api/v1/qrOrder/create', $this->contract())->assertOk();
        // Статус «отменён» не запускает выдачу.
        $this->postJson('/api/v1/qrOrder/changeStatus/' . self::ORDER_ID, ['status' => 'отменён'])->assertOk();

        self::assertSame(0, TicketModel::where('order_ticket_id', self::ORDER_ID)->count());
        Mail::assertNothingSent();
    }
}
