<?php

declare(strict_types=1);

namespace Tests\Feature\QrOrder;

use App\Mail\OrderToPaid;
use App\Models\Template\TemplateBindingModel;
use App\Models\Template\TemplateModel;
use Carbon\Carbon;
use Illuminate\Support\Facades\Queue;
use Ramsey\Uuid\Uuid as RamseyUuid;
use Shared\Domain\ValueObject\Uuid;
use Tests\TestCase;
use Tickets\Order\OrderTicket\Helpers\FestivalHelper;
use Tickets\Order\OrderTicket\Service\FestivalService;
use Tickets\QrOrder\Application\Step\CreateTicketsStep;
use Tickets\QrOrder\Repositories\QrOrderRepositoryInterface;
use Tickets\Ticket\CreateTickets\Application\GetTicket\TicketResponse;

/**
 * Регресс двух прод-багов детских qr-заказов (2026-06-22):
 *  - Bug 1: qr-пайплайн выдачи теперь читает template_bindings (как классический getTicket) —
 *    детская привязка (event=order_paid, ticket_type=детский) применяется к письму И PDF.
 *  - Bug 2: письмо order_paid подставляет номер заказа в {{ kilter }} (раньше был пуст).
 */
class QrOrderChildTemplateAndNumberTest extends TestCase
{
    use WithQrIngestToken;

    private const ORDER_ID = '7e57da7a-0000-4000-8000-00000000c0de';

    private const CHILD_TYPE = 'fea62b42-0ef5-4fbd-bb10-7a5af7267bdb';

    private const ADULT_TYPE = 'a6dbffb8-9942-44a9-b197-3de8388082c3';

    protected function setUp(): void
    {
        parent::setUp();
        $this->configureQrIngestToken();
    }

    private function makeTemplate(string $slug, string $kind, string $body = 'B'): string
    {
        return TemplateModel::create([
            'id' => RamseyUuid::uuid4()->toString(),
            'slug' => $slug,
            'kind' => $kind,
            'engine' => 'html',
            'title' => $slug,
            'body' => $body,
            'active' => true,
            'is_system' => false,
        ])->id;
    }

    /** Создаёт qr-заказ со статусом «создан» (билеты не выпускаются) с одним гостем заданного типа. */
    private function persistChildOrder(string $orderId, string $ticketTypeId): void
    {
        $contract = [
            'order_id' => $orderId,
            'user' => ['name' => 'Родитель', 'city' => 'Москва', 'phone' => '+70000000000'],
            'price' => ['total' => 400],
            'external_order_no' => '777',
            'order_data' => [
                'type_order' => 'regular',
                'festival' => ['id' => FestivalHelper::UUID_FESTIVAL, 'title' => 'СИСТО ОСЕНЬ'],
                'status' => 'создан',
                'email' => 'parent@example.com',
            ],
            'guests' => [
                ['name' => 'Ребёнок Тест', 'email' => 'parent@example.com',
                    'type_ticket' => ['id' => $ticketTypeId, 'title' => 'Детский оргвзнос', 'options' => []]],
            ],
        ];
        $this->postJson('/api/v1/qrOrder/create', $contract, $this->qrIngestHeaders())->assertOk();
    }

    public function test_qr_pipeline_applies_child_template_binding_to_email_and_pdf(): void
    {
        Queue::fake();

        // Детские шаблоны + активная привязка (event=order_paid, ticket_type=детский, оба слота).
        $emailTpl = $this->makeTemplate('TypeTicketMailOrderToPaidChild', 'email');
        $pdfTpl = $this->makeTemplate('TypeTicketPdfChild', 'pdf');
        TemplateBindingModel::create([
            'id' => RamseyUuid::uuid4()->toString(),
            'event' => 'order_paid',
            'ticket_type_id' => self::CHILD_TYPE,
            'email_template_id' => $emailTpl,
            'pdf_template_id' => $pdfTpl,
            'is_default' => false,
            'active' => true,
        ]);

        $this->persistChildOrder(self::ORDER_ID, self::CHILD_TYPE);

        $order = app(QrOrderRepositoryInterface::class)->findById(new Uuid(self::ORDER_ID));
        $carry = app(CreateTicketsStep::class)->handle($order, []);

        $response = $carry['responses'][0];
        // Привязка применилась: письмо и PDF — детские (раньше уходили дефолтными).
        self::assertSame('TypeTicketMailOrderToPaidChild', $response->getEmailView());
        self::assertSame('TypeTicketPdfChild', $response->getFestivalView());
    }

    public function test_qr_pipeline_keeps_default_when_no_binding_matches(): void
    {
        Queue::fake();

        // Привязка только для детского типа; заказ — на взрослый тип → привязка не подходит.
        $emailTpl = $this->makeTemplate('TypeTicketMailOrderToPaidChild', 'email');
        TemplateBindingModel::create([
            'id' => RamseyUuid::uuid4()->toString(),
            'event' => 'order_paid',
            'ticket_type_id' => self::CHILD_TYPE,
            'email_template_id' => $emailTpl,
            'is_default' => false,
            'active' => true,
        ]);

        $oid = '7e57da7a-1111-4000-8000-00000000c0de';
        $this->persistChildOrder($oid, self::ADULT_TYPE);

        $order = app(QrOrderRepositoryInterface::class)->findById(new Uuid($oid));
        $carry = app(CreateTicketsStep::class)->handle($order, []);

        $response = $carry['responses'][0];
        // Нет ticket_type_festival и нет подходящей привязки → базовое поведение (не детский шаблон).
        self::assertNotSame('TypeTicketMailOrderToPaidChild', $response->getEmailView());
        self::assertSame('pdf', $response->getFestivalView());
    }

    public function test_order_paid_email_renders_order_number_in_kilter_placeholder(): void
    {
        // DB-шаблон order_paid с плейсхолдером номера заказа.
        $this->makeTemplate('orderToPaid', 'email', 'Оргвзнос по Заказу № {{ kilter }} подтверждён');

        // Имя фестиваля резолвится отдельной осью (тест проверяет только {{ kilter }}).
        $this->mock(FestivalService::class, static function ($m): void {
            $m->shouldReceive('getFestivalNameByTicketType')->andReturn('СИСТО ОСЕНЬ 2026');
        });

        $ticket = new TicketResponse(
            name: 'Гость',
            kilter: 12,
            uuid: Uuid::random(),
            status: 'paid',
            email: 'g@example.com',
            phone: '',
            city: '',
            comment: null,
            date_order: Carbon::now(),
            festivalView: null, // null → вложение PDF пропускается (рендерим только тело)
            emailView: null,    // null → slug 'orderToPaid'
        );

        // orderNo (external_order_no qr) = 777 → должен попасть в {{ kilter }}.
        $html = (new OrderToPaid([$ticket], Uuid::random(), null, null, 777))->render();

        self::assertStringContainsString('Заказу № 777', $html);
        self::assertStringNotContainsString('Заказу №  подтверждён', $html);
    }

    public function test_order_paid_email_falls_back_to_ticket_kilter_when_no_order_number(): void
    {
        $this->makeTemplate('orderToPaid', 'email', 'Номер: {{ kilter }}');

        $this->mock(FestivalService::class, static function ($m): void {
            $m->shouldReceive('getFestivalNameByTicketType')->andReturn('СИСТО ОСЕНЬ 2026');
        });

        $ticket = new TicketResponse(
            name: 'Гость',
            kilter: 999,
            uuid: Uuid::random(),
            status: 'paid',
            email: 'g@example.com',
            phone: '',
            city: '',
            comment: null,
            date_order: Carbon::now(),
            festivalView: null,
            emailView: null,
        );

        // orderNo не передан → берётся kilter билета (классический org-флоу).
        $html = (new OrderToPaid([$ticket], Uuid::random()))->render();

        self::assertStringContainsString('Номер: 999', $html);
    }
}
