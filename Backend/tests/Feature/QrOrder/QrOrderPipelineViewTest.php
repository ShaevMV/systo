<?php

declare(strict_types=1);

namespace Tests\Feature\QrOrder;

use Database\Seeders\TypeTicketsSeeder;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Shared\Domain\ValueObject\Uuid;
use Tests\TestCase;
use Tickets\Order\OrderTicket\Helpers\FestivalHelper;
use Tickets\QrOrder\Application\Issuance\IssueOrderJob;
use Tickets\QrOrder\Application\Pipeline\QrOrderPipelineReader;

/**
 * Ф5: «видеть весь путь» qr-заказа — ридер собирает заказ + билеты(PDF-ссылки) + письма(статусы) +
 * историю с шагами пайплайна (step_*). Плюс ссылки на PDF для скачивания.
 */
class QrOrderPipelineViewTest extends TestCase
{
    use WithQrIngestToken;

    private const ORDER_ID = 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb';

    protected function setUp(): void
    {
        parent::setUp();
        $this->configureQrIngestToken();
    }

    private function persistAndIssue(): void
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

        app()->call([new IssueOrderJob(new Uuid(self::ORDER_ID)), 'handle']);
    }

    public function test_pipeline_assembles_order_tickets_emails_history(): void
    {
        Mail::fake();
        Queue::fake();
        $this->persistAndIssue();

        $pipeline = app(QrOrderPipelineReader::class)->pipeline(new Uuid(self::ORDER_ID));

        $this->assertNotNull($pipeline);
        $this->assertSame(self::ORDER_ID, $pipeline['order']['id']);

        // Билет создан, PDF-ссылка построена.
        $this->assertCount(1, $pipeline['tickets']);
        $this->assertStringContainsString('.pdf', $pipeline['tickets'][0]['pdf_url']);

        // Письма заказа отслеживаются: «заказ создан» (order_created) при приёме в статусе «создан»
        // + «оплачен» (order_paid) при выдаче. Оба статуса queued под Queue::fake.
        $this->assertNotEmpty($pipeline['emails']);
        $emailEvents = array_column($pipeline['emails'], 'event');
        $this->assertContains('order_created', $emailEvents);
        $this->assertContains('order_paid', $emailEvents);

        // История содержит шаги пайплайна (step_*) и финальное issued.
        $names = array_column($pipeline['history'], 'event_name');
        $this->assertContains('issued', $names);
        $this->assertNotEmpty(array_filter($names, static fn (string $n): bool => str_starts_with($n, 'step_')));

        // Секция доставки в baza присутствует (наполняется после перевода qr на BazaDeliveryDispatcher).
        $this->assertArrayHasKey('baza', $pipeline);
    }

    public function test_ticket_pdf_urls(): void
    {
        Mail::fake();
        Queue::fake();
        $this->persistAndIssue();

        $urls = app(QrOrderPipelineReader::class)->ticketPdfUrls(new Uuid(self::ORDER_ID));

        $this->assertCount(1, $urls);
        $this->assertStringContainsString('storage/tickets/', $urls[0]);
    }

    public function test_pipeline_null_for_unknown_order(): void
    {
        $this->assertNull(app(QrOrderPipelineReader::class)->pipeline(Uuid::random()));
    }
}
