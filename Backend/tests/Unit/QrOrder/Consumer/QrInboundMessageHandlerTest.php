<?php

declare(strict_types=1);

namespace Tests\Unit\QrOrder\Consumer;

use Illuminate\Support\Facades\Bus;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tickets\EmailDelivery\Application\QrEmailIntake;
use Tickets\History\Repositories\HistoryRepositoryInterface;
use Tickets\QrOrder\Application\Consumer\QrHandleOutcome;
use Tickets\QrOrder\Application\Consumer\QrInboundMessageHandler;
use Tickets\QrOrder\Dto\QrOrderDto;
use Tickets\QrOrder\Repositories\QrOrderRepositoryInterface;

/**
 * Routing-ядро консьюмера qr→org: по типу сообщения вызывает ТЕ ЖЕ точки приёма, что HTTP.
 * Мокаем репозитории (интерфейсы) в контейнере + Bus::fake (IssueOrderJob); реальный
 * QrOrderApplication резолвится контейнером — final-класс не мокаем. БД не дёргается.
 */
class QrInboundMessageHandlerTest extends TestCase
{
    private const ORDER_ID = '550e8400-e29b-41d4-a716-446655440000';

    protected function setUp(): void
    {
        parent::setUp();
        Bus::fake(); // IssueOrderJob::dispatch не уходит в реальную очередь
    }

    public function test_unknown_type_goes_to_dlq(): void
    {
        $this->bindRepo();
        $this->bindEmails();

        $this->assertSame(QrHandleOutcome::Dlq, $this->handler()->handle('qr.unknown', []));
    }

    public function test_order_create_valid_acks(): void
    {
        $repo = $this->bindRepo();
        $repo->shouldReceive('existsById')->once()->andReturnFalse();
        $repo->shouldReceive('create')->once()->andReturnTrue();
        $this->bindEmails();

        $this->assertSame(
            QrHandleOutcome::Ack,
            $this->handler()->handle('qr.order.create', $this->orderBody()),
        );
    }

    public function test_order_create_idempotent_acks_without_create(): void
    {
        $repo = $this->bindRepo();
        $repo->shouldReceive('existsById')->once()->andReturnTrue();
        $repo->shouldNotReceive('create');
        $this->bindEmails();

        $this->assertSame(
            QrHandleOutcome::Ack,
            $this->handler()->handle('qr.order.create', $this->orderBody()),
        );
    }

    public function test_order_create_bad_contract_goes_to_dlq(): void
    {
        $repo = $this->bindRepo();
        $repo->shouldNotReceive('create');
        $this->bindEmails();

        // нет order_id → QrOrderDto::fromQrContract бросает InvalidArgumentException → DLQ
        $this->assertSame(
            QrHandleOutcome::Dlq,
            $this->handler()->handle('qr.order.create', ['order_data' => ['email' => 'a@b.c']]),
        );
    }

    public function test_order_status_acks_when_order_exists(): void
    {
        $repo = $this->bindRepo();
        $repo->shouldReceive('findById')->once()->andReturn($this->dto());
        $repo->shouldReceive('changeStatus')->once()->andReturnTrue();
        $this->bindEmails();

        $body = ['order_id' => self::ORDER_ID, 'status' => 'создан'];
        $this->assertSame(QrHandleOutcome::Ack, $this->handler()->handle('qr.order.status', $body));
    }

    public function test_order_status_retries_when_order_not_found(): void
    {
        $repo = $this->bindRepo();
        $repo->shouldReceive('findById')->once()->andReturnNull();
        $this->bindEmails();

        $body = ['order_id' => self::ORDER_ID, 'status' => 'оплачен'];
        $this->assertSame(QrHandleOutcome::Retry, $this->handler()->handle('qr.order.status', $body));
    }

    public function test_order_status_missing_fields_goes_to_dlq(): void
    {
        $this->bindRepo();
        $this->bindEmails();

        $this->assertSame(
            QrHandleOutcome::Dlq,
            $this->handler()->handle('qr.order.status', ['order_id' => self::ORDER_ID]),
        );
    }

    public function test_email_invalid_goes_to_dlq(): void
    {
        $this->bindRepo();
        $emails = $this->bindEmails();
        $emails->shouldReceive('ingest')->once()->andReturn(['status' => 'invalid', 'message' => 'нет email']);

        $this->assertSame(
            QrHandleOutcome::Dlq,
            $this->handler()->handle('qr.email.send', ['event' => 'user_registered']),
        );
    }

    public function test_email_accepted_acks(): void
    {
        $this->bindRepo();
        $emails = $this->bindEmails();
        $emails->shouldReceive('ingest')->once()->andReturn(['status' => 'accepted', 'email_id' => 'x']);

        $this->assertSame(
            QrHandleOutcome::Ack,
            $this->handler()->handle('qr.email.send', ['event' => 'user_registered', 'email' => 'a@b.c']),
        );
    }

    public function test_transient_error_retries(): void
    {
        $repo = $this->bindRepo();
        $repo->shouldReceive('existsById')->andReturnFalse();
        $repo->shouldReceive('create')->andThrow(new \RuntimeException('db down'));
        $this->bindEmails();

        $this->assertSame(
            QrHandleOutcome::Retry,
            $this->handler()->handle('qr.order.create', $this->orderBody()),
        );
    }

    // ── helpers ──────────────────────────────────────────────────────────────

    private function bindRepo(): MockInterface
    {
        $repo = Mockery::mock(QrOrderRepositoryInterface::class);
        $history = Mockery::mock(HistoryRepositoryInterface::class);
        $history->shouldReceive('save')->andReturnNull();

        $this->app->instance(QrOrderRepositoryInterface::class, $repo);
        $this->app->instance(HistoryRepositoryInterface::class, $history);

        return $repo;
    }

    private function bindEmails(): MockInterface
    {
        $emails = Mockery::mock(QrEmailIntake::class);
        $this->app->instance(QrEmailIntake::class, $emails);

        return $emails;
    }

    private function handler(): QrInboundMessageHandler
    {
        return $this->app->make(QrInboundMessageHandler::class);
    }

    /**
     * @return array<string, mixed>
     */
    private function orderBody(string $status = ''): array
    {
        return [
            'order_id' => self::ORDER_ID,
            'order_data' => ['email' => 'guest@example.com', 'status' => $status],
        ];
    }

    private function dto(): QrOrderDto
    {
        return QrOrderDto::fromQrContract($this->orderBody('создан'));
    }
}
