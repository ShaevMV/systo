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
use RuntimeException;
use Shared\Domain\ValueObject\Uuid;
use Tests\TestCase;
use Tickets\Order\OrderTicket\Helpers\FestivalHelper;
use Tickets\QrOrder\Application\Issuance\IssueOrderJob;
use Tickets\QrOrder\Application\Job\PushTicketToBazaJob;
use Tickets\QrOrder\Repositories\QrOrderRepositoryInterface;
use Tickets\Questionnaire\Domain\DomainEvent\ProcessTelegramSend;
use Tickets\Ticket\CreateTickets\Domain\ProcessCreatingQRCode;

/**
 * Pipeline выдачи (оркестратор IssueOrderJob, стратегия REGULAR): на каждого гостя создаётся
 * билет в tickets (order_ticket_id == id qr-заказа), ставится PDF/QR в очередь и отправляется
 * ОДНО письмо со всеми PDF. Оркестратор запускаем синхронно (handle), очередь/почта — fake.
 */
class QrOrderPipelineTest extends TestCase
{
    private const ORDER_ID = '44444444-4444-4444-4444-444444444444';

    protected function setUp(): void
    {
        parent::setUp();
        Sanctum::actingAs(User::factory()->create(), ['qr:ingest']);
    }

    private function persistOrder(): void
    {
        $contract = [
            'order_id' => self::ORDER_ID,
            'user' => ['name' => 'Иван', 'city' => 'Москва', 'phone' => '+70000000000'],
            'price' => ['total' => 4200],
            'order_data' => [
                'type_order' => 'обычный',
                'festival' => ['id' => FestivalHelper::UUID_FESTIVAL, 'title' => 'Систо'],
                'status' => 'создан',
                'email' => 'buyer@example.com',
            ],
            'guests' => [
                ['name' => 'Иван Гость', 'email' => 'guest@example.com', 'telegram' => 'ivan_guest',
                 'type_ticket' => ['id' => TypeTicketsSeeder::ID_FOR_FIRST_WAVE, 'title' => 'Оргвзнос', 'options' => []]],
            ],
        ];
        $this->postJson('/api/v1/qrOrder/create', $contract)->assertOk();
    }

    public function test_orchestrator_issues_tickets_and_sends_one_email(): void
    {
        Mail::fake();
        Queue::fake();
        $this->persistOrder();

        // Запускаем оркестратор синхронно (в проде — асинхронно через очередь).
        app()->call([new IssueOrderJob(new Uuid(self::ORDER_ID)), 'handle']);

        // Билет создан и привязан к заказу qr (order_ticket_id == id qr-заказа).
        $this->assertDatabaseHas('tickets', [
            'order_ticket_id' => self::ORDER_ID,
            'name' => 'Иван Гость',
        ]);
        self::assertSame(1, TicketModel::where('order_ticket_id', self::ORDER_ID)->count());

        // PDF/QR поставлен в очередь; письмо со всеми PDF отправлено ровно одно.
        Queue::assertPushed(ProcessCreatingQRCode::class);
        Mail::assertSent(OrderToPaid::class, 1);

        // Запись билета в Baza — отдельной изолированной задачей (на каждый билет).
        Queue::assertPushed(PushTicketToBazaJob::class, 1);

        // Уведомление гостя в Telegram — отдельной задачей (у гостя задан telegram).
        Queue::assertPushed(ProcessTelegramSend::class, 1);
    }

    public function test_skips_telegram_when_guest_has_none(): void
    {
        Mail::fake();
        Queue::fake();

        $oid = '55555555-5555-5555-5555-555555555555';
        $contract = [
            'order_id' => $oid,
            'user' => ['name' => 'Без ТГ', 'city' => 'Тверь', 'phone' => '+70000000001'],
            'price' => ['total' => 4200],
            'order_data' => [
                'type_order' => 'обычный',
                'festival' => ['id' => FestivalHelper::UUID_FESTIVAL, 'title' => 'Систо'],
                'status' => 'создан',
                'email' => 'notg@example.com',
            ],
            'guests' => [
                ['name' => 'Гость Без ТГ', 'email' => 'notg@example.com',
                 'type_ticket' => ['id' => TypeTicketsSeeder::ID_FOR_FIRST_WAVE, 'title' => 'Оргвзнос', 'options' => []]],
            ],
        ];
        $this->postJson('/api/v1/qrOrder/create', $contract)->assertOk();

        app()->call([new IssueOrderJob(new Uuid($oid)), 'handle']);

        // Нет telegram у гостя → задача в бот не ставится (мягкая валидация), остальное работает.
        Queue::assertNotPushed(ProcessTelegramSend::class);
        Queue::assertPushed(PushTicketToBazaJob::class, 1);
    }

    public function test_failed_job_resets_issued_at_for_retry(): void
    {
        $this->persistOrder();

        // Имитируем: при dispatch заказ помечен выданным, затем задача упала окончательно.
        $repository = app(QrOrderRepositoryInterface::class);
        $repository->markIssued(new Uuid(self::ORDER_ID), now());
        (new IssueOrderJob(new Uuid(self::ORDER_ID)))->failed(new RuntimeException('boom'));

        // issued_at сброшен → повторный «оплачен» от qr сможет переподнять выдачу (нет «выдан без билетов»).
        $this->assertDatabaseHas('qr_orders', ['id' => self::ORDER_ID, 'issued_at' => null]);
    }
}
