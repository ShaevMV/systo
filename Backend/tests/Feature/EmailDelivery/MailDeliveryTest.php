<?php

declare(strict_types=1);

namespace Tests\Feature\EmailDelivery;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Shared\Domain\Criteria\Order;
use Shared\Domain\ValueObject\Uuid;
use Tests\TestCase;
use Tickets\EmailDelivery\Application\EmailContext;
use Tickets\EmailDelivery\Application\EmailDeliveryApplication;
use Tickets\EmailDelivery\Application\GetList\EmailMessageGetListQuery;
use Tickets\EmailDelivery\Application\Job\SendEmailJob;
use Tickets\EmailDelivery\Application\MailDispatcher;
use Tickets\EmailDelivery\Domain\EmailEvent;
use Tickets\EmailDelivery\Domain\ValueObject\EmailStatus;
use Tickets\EmailDelivery\Dto\EmailMessageDto;
use Tickets\EmailDelivery\Repositories\EmailMessageRepositoryInterface;
use Tickets\History\Domain\ActorType;
use Tickets\History\Repositories\HistoryRepositoryInterface;

/** Тестовое письмо: сериализуемо, build не зависит от шаблонов (под Mail::fake не рендерится). */
class StubMailable extends Mailable
{
    public function build(): static
    {
        return $this->html('<p>stub</p>');
    }
}

/**
 * Ф2: контроль пути письма — постановка в очередь (queued) → отправка (sent) / сбой (failed),
 * повторная отправка из админки, список с фильтром. «Где застряло» = статус + error.
 */
class MailDeliveryTest extends TestCase
{
    use RefreshDatabase;

    private function ctx(string $to = 'guest@example.com'): EmailContext
    {
        return new EmailContext(
            recipient: $to,
            source: 'qr_pipeline',
            actorType: ActorType::QR,
            aggregateType: 'qr_order',
            aggregateId: Uuid::random()->value(),
        );
    }

    public function test_dispatcher_creates_queued_and_dispatches_job(): void
    {
        Queue::fake();

        $id = app(MailDispatcher::class)->send(EmailEvent::ORDER_PAID, $this->ctx(), new StubMailable());

        $this->assertDatabaseHas('email_messages', [
            'id' => $id->value(),
            'status' => EmailStatus::QUEUED,
            'event' => EmailEvent::ORDER_PAID,
            'source' => 'qr_pipeline',
        ]);
        $this->assertDatabaseHas('domain_history', [
            'aggregate_id' => $id->value(),
            'aggregate_type' => 'email',
            'event_name' => 'email_queued',
            'actor_type' => ActorType::QR,
        ]);
        Queue::assertPushed(SendEmailJob::class);
    }

    public function test_send_job_marks_sent(): void
    {
        Mail::fake();
        Queue::fake(); // чтобы dispatcher не запустил job сам — запустим вручную

        $id = app(MailDispatcher::class)->send(EmailEvent::ORDER_PAID, $this->ctx(), new StubMailable());

        (new SendEmailJob($id->value()))->handle(
            app(EmailMessageRepositoryInterface::class),
            app(HistoryRepositoryInterface::class),
        );

        Mail::assertSent(StubMailable::class);
        $this->assertDatabaseHas('email_messages', ['id' => $id->value(), 'status' => EmailStatus::SENT]);
        $this->assertNotNull(app(EmailMessageRepositoryInterface::class)->findById($id)?->getStatus());
        $this->assertDatabaseHas('domain_history', [
            'aggregate_id' => $id->value(),
            'event_name' => 'email_sent',
        ]);
    }

    public function test_send_job_marks_failed_without_mailable(): void
    {
        $repo = app(EmailMessageRepositoryInterface::class);
        $id = Uuid::random();
        $repo->create(
            EmailMessageDto::queued($id, EmailEvent::ORDER_PAID, $this->ctx(), 'orderToPaid', bin2hex(random_bytes(16))),
            null, // нет сохранённого Mailable → отправка невозможна
        );

        (new SendEmailJob($id->value()))->handle($repo, app(HistoryRepositoryInterface::class));

        $this->assertDatabaseHas('email_messages', ['id' => $id->value(), 'status' => EmailStatus::FAILED]);
        $this->assertNotNull($repo->findById($id));
    }

    public function test_resend_requeues_and_dispatches(): void
    {
        Mail::fake();
        Queue::fake();

        $id = app(MailDispatcher::class)->send(EmailEvent::ORDER_CANCEL, $this->ctx(), new StubMailable());
        app(EmailMessageRepositoryInterface::class)->markFailed($id, 'smtp недоступен');

        $ok = app(EmailDeliveryApplication::class)->resend($id, null);

        $this->assertTrue($ok);
        $this->assertDatabaseHas('email_messages', ['id' => $id->value(), 'status' => EmailStatus::QUEUED]);
        Queue::assertPushed(SendEmailJob::class);
    }

    public function test_resend_unknown_returns_false(): void
    {
        $this->assertFalse(app(EmailDeliveryApplication::class)->resend(Uuid::random(), null));
    }

    public function test_getlist_filters_by_status(): void
    {
        $repo = app(EmailMessageRepositoryInterface::class);

        $sentId = Uuid::random();
        $repo->create(EmailMessageDto::queued($sentId, EmailEvent::ORDER_PAID, $this->ctx('a@x.ru'), 'orderToPaid', bin2hex(random_bytes(16))), null);
        $repo->markSent($sentId, null);

        $failedId = Uuid::random();
        $repo->create(EmailMessageDto::queued($failedId, EmailEvent::ORDER_CANCEL, $this->ctx('b@x.ru'), 'orderToCancel', bin2hex(random_bytes(16))), null);
        $repo->markFailed($failedId, 'bounce');

        $resp = app(EmailDeliveryApplication::class)->getList(
            new EmailMessageGetListQuery(['status' => EmailStatus::FAILED], Order::none(), 1, 20),
        );

        $this->assertSame(1, $resp->getTotalCount());
        $this->assertCount(1, $resp->getCollection());
    }
}
