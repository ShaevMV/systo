<?php

declare(strict_types=1);

namespace Tests\Feature\QrOrder;

use App\Mail\OrderListApproved;
use App\Mail\OrderToLiveTicketIssued;
use App\Mail\OrderToPaid;
use App\Mail\OrderToPaidFriendly;
use App\Models\Tickets\TicketModel;
use Database\Seeders\TypeTicketsSeeder;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use RuntimeException;
use Shared\Domain\ValueObject\Uuid;
use Tests\TestCase;
use Tickets\Order\OrderTicket\Helpers\FestivalHelper;
use Tickets\QrOrder\Application\Issuance\IssueOrderJob;
use Tickets\QrOrder\Application\Job\LinkLiveTicketJob;
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
    use WithQrIngestToken;

    private const ORDER_ID = '44444444-4444-4444-4444-444444444444';

    protected function setUp(): void
    {
        parent::setUp();
        // S2S-канал закрыт сервисным ключом qr (X-QR-Token) — настраиваем валидный ключ.
        $this->configureQrIngestToken();
    }

    private function persistOrder(): void
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
                ['name' => 'Иван Гость', 'email' => 'guest@example.com', 'telegram' => 'ivan_guest',
                 'type_ticket' => ['id' => TypeTicketsSeeder::ID_FOR_FIRST_WAVE, 'title' => 'Оргвзнос', 'options' => []]],
            ],
        ];
        $this->postJson('/api/v1/qrOrder/create', $contract, $this->qrIngestHeaders())->assertOk();
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

        // История: при выдаче записано событие issued (actor=qr).
        $this->assertDatabaseHas('domain_history', [
            'aggregate_id' => self::ORDER_ID,
            'event_name' => 'issued',
            'actor_type' => 'qr',
        ]);
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
                'type_order' => 'regular',
                'festival' => ['id' => FestivalHelper::UUID_FESTIVAL, 'title' => 'Систо'],
                'status' => 'создан',
                'email' => 'notg@example.com',
            ],
            'guests' => [
                ['name' => 'Гость Без ТГ', 'email' => 'notg@example.com',
                 'type_ticket' => ['id' => TypeTicketsSeeder::ID_FOR_FIRST_WAVE, 'title' => 'Оргвзнос', 'options' => []]],
            ],
        ];
        $this->postJson('/api/v1/qrOrder/create', $contract, $this->qrIngestHeaders())->assertOk();

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

    public function test_friendly_order_sends_friendly_email(): void
    {
        Mail::fake();
        Queue::fake();

        $oid = '66666666-6666-6666-6666-666666666666';
        $contract = [
            'order_id' => $oid,
            'user' => ['name' => 'Френд', 'city' => 'Пермь', 'phone' => '+70000000002'],
            'price' => ['total' => 4200],
            'order_data' => [
                'type_order' => 'friendly',
                'festival' => ['id' => FestivalHelper::UUID_FESTIVAL, 'title' => 'Систо'],
                'status' => 'создан',
                'email' => 'friend@example.com',
            ],
            'guests' => [
                ['name' => 'Френд Гость', 'email' => 'friend@example.com',
                 'type_ticket' => ['id' => TypeTicketsSeeder::ID_FOR_FIRST_WAVE, 'title' => 'Оргвзнос', 'options' => []]],
            ],
        ];
        $this->postJson('/api/v1/qrOrder/create', $contract, $this->qrIngestHeaders())->assertOk();

        app()->call([new IssueOrderJob(new Uuid($oid)), 'handle']);

        // Friendly-заказ → friendly-письмо; обычное OrderToPaid НЕ отправляется.
        Mail::assertSent(OrderToPaidFriendly::class, 1);
        Mail::assertNotSent(OrderToPaid::class);

        // Билет всё равно создаётся и пишется в Baza.
        self::assertSame(1, TicketModel::where('order_ticket_id', $oid)->count());
        Queue::assertPushed(PushTicketToBazaJob::class, 1);
    }

    public function test_list_order_sends_list_email_and_creates_ticket(): void
    {
        Mail::fake();
        Queue::fake();

        $oid = '77777777-7777-7777-7777-777777777777';
        $contract = [
            'order_id' => $oid,
            'user' => ['name' => 'Получатель', 'city' => 'Сочи', 'phone' => '+70000000003'],
            // цены нет
            'order_data' => [
                'type_order' => 'list',
                'festival' => ['id' => FestivalHelper::UUID_FESTIVAL, 'title' => 'Систо'],
                'curator' => ['id' => '88888888-8888-8888-8888-888888888888', 'email' => 'curator@example.com', 'name' => 'Иван Куратор'],
                'location' => ['id' => '99999999-9999-9999-9999-999999999999', 'name' => 'Сцена А'],
                'project' => 'Смена 1',
                'status' => 'создан',
                'email' => 'recipient@example.com',
            ],
            'guests' => [
                ['name' => 'Гость Списка', 'email' => 'g.list@example.com',
                 'type_ticket' => ['id' => TypeTicketsSeeder::ID_FOR_FIRST_WAVE, 'title' => 'Оргвзнос', 'options' => []]],
            ],
        ];
        $this->postJson('/api/v1/qrOrder/create', $contract, $this->qrIngestHeaders())->assertOk();

        app()->call([new IssueOrderJob(new Uuid($oid)), 'handle']);

        // Список → письмо OrderListApproved (а не OrderToPaid), билет создан, Baza-задача поставлена.
        Mail::assertSent(OrderListApproved::class, 1);
        Mail::assertNotSent(OrderToPaid::class);
        self::assertSame(1, TicketModel::where('order_ticket_id', $oid)->count());
        Queue::assertPushed(PushTicketToBazaJob::class, 1);
    }

    public function test_live_order_no_pdf_links_and_sends_live_email(): void
    {
        Mail::fake();
        Queue::fake();

        $oid = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';
        $contract = [
            'order_id' => $oid,
            'user' => ['name' => 'Лайв', 'city' => 'Уфа', 'phone' => '+70000000004'],
            'order_data' => [
                'type_order' => 'live',
                'festival' => ['id' => FestivalHelper::UUID_FESTIVAL, 'title' => 'Систо'],
                'status' => 'создан',
                'email' => 'live.recipient@example.com',
            ],
            'guests' => [
                ['name' => 'Лайв Гость', 'email' => 'g.live@example.com', 'number' => 777,
                 'type_ticket' => ['id' => TypeTicketsSeeder::ID_FOR_FIRST_WAVE, 'title' => 'Живой', 'options' => []]],
            ],
        ];
        $this->postJson('/api/v1/qrOrder/create', $contract, $this->qrIngestHeaders())->assertOk();

        app()->call([new IssueOrderJob(new Uuid($oid)), 'handle']);

        // Живой: письмо о выдаче (без PDF), связка с live_tickets поставлена;
        // PDF (ProcessCreatingQRCode) и el_tickets (PushTicketToBazaJob) НЕ задействованы.
        Mail::assertSent(OrderToLiveTicketIssued::class, 1);
        Queue::assertPushed(LinkLiveTicketJob::class, 1);
        Queue::assertNotPushed(ProcessCreatingQRCode::class);
        Queue::assertNotPushed(PushTicketToBazaJob::class);
        self::assertSame(1, TicketModel::where('order_ticket_id', $oid)->count());
    }

    public function test_rerun_does_not_duplicate_tickets(): void
    {
        Mail::fake();
        Queue::fake();
        $this->persistOrder();

        // Двойной прогон оркестратора (имитация ретрая/переотправки «оплачен» после сбоя).
        app()->call([new IssueOrderJob(new Uuid(self::ORDER_ID)), 'handle']);
        app()->call([new IssueOrderJob(new Uuid(self::ORDER_ID)), 'handle']);

        // Билет не задублировался (детерминированный id), PDF поставлен один раз (только для нового).
        self::assertSame(1, TicketModel::where('order_ticket_id', self::ORDER_ID)->count());
        Queue::assertPushed(ProcessCreatingQRCode::class, 1);
    }
}
