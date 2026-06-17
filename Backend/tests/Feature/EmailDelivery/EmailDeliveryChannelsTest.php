<?php

declare(strict_types=1);

namespace Tests\Feature\EmailDelivery;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Queue;
use Tests\Feature\QrOrder\WithQrIngestToken;
use Tests\TestCase;
use Tickets\EmailDelivery\Application\EmailContext;
use Tickets\EmailDelivery\Application\EmailDeliveryApplication;
use Tickets\EmailDelivery\Application\MailDispatcher;
use Tickets\EmailDelivery\Domain\EmailEvent;
use Tickets\EmailDelivery\Domain\ValueObject\EmailStatus;
use Tickets\EmailDelivery\Repositories\EmailMessageRepositoryInterface;
use Tickets\History\Domain\ActorType;

/** Локальное тестовое письмо (уникальное имя — не конфликтует со StubMailable из MailDeliveryTest). */
class ChannelStubMailable extends Mailable
{
    public function build(): static
    {
        return $this->html('<p>stub</p>');
    }
}

/**
 * Ф3 (пиксель прочтения) + Ф4 (S2S-приём писем от витрины qr).
 */
class EmailDeliveryChannelsTest extends TestCase
{
    use RefreshDatabase;
    use WithQrIngestToken;

    protected function setUp(): void
    {
        parent::setUp();
        $this->configureQrIngestToken();
    }

    /** Создаёт письмо в статусе sent и возвращает [id, token]. */
    private function sentMessage(): array
    {
        Queue::fake();
        $id = app(MailDispatcher::class)->send(
            EmailEvent::ORDER_PAID,
            new EmailContext(recipient: 'guest@example.com', source: 'qr_pipeline', actorType: ActorType::QR),
            new ChannelStubMailable(),
        );
        $repo = app(EmailMessageRepositoryInterface::class);
        $repo->markSent($id, null);

        return [$id, $repo->findById($id)->getTrackingToken()];
    }

    // --- Ф3: пиксель прочтения --------------------------------------------

    public function test_open_pixel_marks_sent_message_opened(): void
    {
        [$id, $token] = $this->sentMessage();

        app(EmailDeliveryApplication::class)->registerOpen($token);

        $this->assertDatabaseHas('email_messages', ['id' => $id->value(), 'status' => EmailStatus::OPENED]);
        $this->assertNotNull(app(EmailMessageRepositoryInterface::class)->findById($id)->toArray()['opened_at']);
        $this->assertDatabaseHas('domain_history', ['aggregate_id' => $id->value(), 'event_name' => 'email_opened']);
    }

    public function test_open_pixel_idempotent(): void
    {
        [$id, $token] = $this->sentMessage();
        $app = app(EmailDeliveryApplication::class);

        $app->registerOpen($token);
        $app->registerOpen($token);

        // Только одно событие открытия в истории (повтор пикселя не плодит записи).
        $this->assertSame(1, \App\Models\History\DomainHistoryModel::query()
            ->where('aggregate_id', $id->value())
            ->where('event_name', 'email_opened')
            ->count());
    }

    public function test_open_pixel_noop_when_not_sent(): void
    {
        Queue::fake();
        $id = app(MailDispatcher::class)->send(
            EmailEvent::ORDER_PAID,
            new EmailContext(recipient: 'q@example.com', source: 'qr_pipeline', actorType: ActorType::QR),
            new ChannelStubMailable(),
        );
        $token = app(EmailMessageRepositoryInterface::class)->findById($id)->getTrackingToken();

        // Письмо ещё queued → пиксель не должен переводить в opened.
        app(EmailDeliveryApplication::class)->registerOpen($token);

        $this->assertDatabaseHas('email_messages', ['id' => $id->value(), 'status' => EmailStatus::QUEUED]);
    }

    public function test_open_pixel_endpoint_returns_gif(): void
    {
        [, $token] = $this->sentMessage();

        $response = $this->get('/api/v1/mail/open/' . $token . '.gif');

        $response->assertOk();
        $this->assertSame('image/gif', $response->headers->get('Content-Type'));
    }

    // --- Ф4: S2S-приём писем от qr ----------------------------------------

    public function test_intake_creates_tracked_message(): void
    {
        Queue::fake();

        $response = $this->postJson('/api/v1/emailNotification/send', [
            'event' => EmailEvent::PASSWORD_RESET,
            'email' => 'user@example.com',
            'vars' => ['link' => 'https://x/reset'],
            'external_id' => 'qr-pwd-1',
        ], $this->qrIngestHeaders());

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseHas('email_messages', [
            'recipient' => 'user@example.com',
            'event' => EmailEvent::PASSWORD_RESET,
            'source' => 'qr_intake',
            'status' => EmailStatus::QUEUED,
        ]);
    }

    public function test_intake_idempotent_by_external_id(): void
    {
        Queue::fake();
        $payload = [
            'event' => EmailEvent::USER_REGISTERED,
            'email' => 'reg@example.com',
            'vars' => ['login' => 'reg@example.com', 'password' => 'secret'],
            'external_id' => 'qr-reg-1',
        ];

        $this->postJson('/api/v1/emailNotification/send', $payload, $this->qrIngestHeaders())->assertOk();
        $this->postJson('/api/v1/emailNotification/send', $payload, $this->qrIngestHeaders())->assertOk();

        // Повтор того же external_id → дубля письма нет.
        $this->assertSame(1, \App\Models\EmailDelivery\EmailMessageModel::query()
            ->where('recipient', 'reg@example.com')
            ->count());
    }

    public function test_intake_rejects_unknown_event(): void
    {
        $this->postJson('/api/v1/emailNotification/send', [
            'event' => 'teleport',
            'email' => 'x@example.com',
        ], $this->qrIngestHeaders())->assertStatus(422);
    }

    public function test_intake_requires_service_token(): void
    {
        // Без X-QR-Token канал закрыт (middleware qr.ingest).
        $this->postJson('/api/v1/emailNotification/send', [
            'event' => EmailEvent::PASSWORD_RESET,
            'email' => 'x@example.com',
        ])->assertStatus(401);
    }
}
