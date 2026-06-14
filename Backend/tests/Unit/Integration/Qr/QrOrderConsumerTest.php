<?php

declare(strict_types=1);

namespace Tests\Unit\Integration\Qr;

use Shared\Integration\Rabbit\EventEnvelope;
use Tests\TestCase;
use Tickets\Integration\Qr\Exception\QrOrderRejectedException;
use Tickets\Integration\Qr\QrOrderConsumer;
use Tickets\Integration\Qr\QrOrderIngestorInterface;

/**
 * Поведение приёмника заказов qr → org (CONTRACT_RFC_v0.md §7):
 * проверка версии схемы, формы payload, дедупликация (at-most-once),
 * делегирование ингестору и трактовка бизнес-отказа.
 */
class QrOrderConsumerTest extends TestCase
{
    /** Фейковый ингестор: считает вызовы, опционально бросает бизнес-отказ. */
    private function fakeIngestor(bool $reject = false): QrOrderIngestorInterface
    {
        return new class($reject) implements QrOrderIngestorInterface {
            public int $calls = 0;

            public function __construct(private bool $reject)
            {
            }

            public function ingest(EventEnvelope $envelope): void
            {
                $this->calls++;
                if ($this->reject) {
                    throw new QrOrderRejectedException('тестовый бизнес-отказ');
                }
            }
        };
    }

    private function consumerWith(QrOrderIngestorInterface $ingestor): QrOrderConsumer
    {
        $this->app->instance(QrOrderIngestorInterface::class, $ingestor);

        return $this->app->make(QrOrderConsumer::class);
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function envelope(array $overrides = []): EventEnvelope
    {
        $defaults = [
            'eventType' => 'order.created',
            'traceId' => 'trace-1',
            'idempotencyKey' => 'order.qr-123',
            'occurredAt' => '2026-06-14T12:00:00+00:00',
            'source' => 'qr',
            'payload' => [
                'order' => [
                    'qr_order_id' => 'qr-123',
                    'type_order' => 'regular',
                    'festival_id' => '9d679bcf-b438-4ddb-ac04-023fa9bff4b8',
                    'email' => 'buyer@example.com',
                ],
                'guests' => [
                    ['name' => 'Иван Гость', 'email' => 'guest@example.com'],
                ],
            ],
            'schemaVersion' => '1.0',
        ];
        $data = array_merge($defaults, $overrides);

        return new EventEnvelope(
            eventType: $data['eventType'],
            traceId: $data['traceId'],
            idempotencyKey: $data['idempotencyKey'],
            occurredAt: $data['occurredAt'],
            source: $data['source'],
            payload: $data['payload'],
            schemaVersion: $data['schemaVersion'],
        );
    }

    public function test_valid_order_is_ingested_and_marked_processed(): void
    {
        // Корректное событие order.created: ингестор вызван один раз,
        // ключ идемпотентности записан в processed_messages, возврат true (ack).
        $ingestor = $this->fakeIngestor();
        $consumer = $this->consumerWith($ingestor);

        $result = $consumer->handle($this->envelope());

        self::assertTrue($result);
        self::assertSame(1, $ingestor->calls);
        $this->assertDatabaseHas('processed_messages', [
            'idempotency_key' => 'order.qr-123',
            'event_type' => 'order.created',
            'source' => 'qr',
            'trace_id' => 'trace-1',
        ]);
    }

    public function test_duplicate_is_acked_without_reingest(): void
    {
        // Повторная доставка того же события (тот же idempotency_key) не должна
        // повторно создавать заказ: ингестор вызывается один раз, в БД одна запись.
        $ingestor = $this->fakeIngestor();
        $consumer = $this->consumerWith($ingestor);

        $first = $consumer->handle($this->envelope());
        $second = $consumer->handle($this->envelope());

        self::assertTrue($first);
        self::assertTrue($second);
        self::assertSame(1, $ingestor->calls, 'Повторная доставка не должна повторно создавать заказ');
        self::assertDatabaseCount('processed_messages', 1);
    }

    public function test_unsupported_schema_major_is_rejected(): void
    {
        // Несовместимая мажорная версия схемы → reject без requeue (мы не понимаем формат),
        // ингестор не вызывается, ключ идемпотентности не занимается.
        $ingestor = $this->fakeIngestor();
        $consumer = $this->consumerWith($ingestor);

        $result = $consumer->handle($this->envelope(['schemaVersion' => '2.0']));

        self::assertFalse($result, 'Несовместимая мажорная версия → reject без requeue');
        self::assertSame(0, $ingestor->calls);
        $this->assertDatabaseMissing('processed_messages', ['idempotency_key' => 'order.qr-123']);
    }

    public function test_unknown_event_type_is_acked_and_skipped(): void
    {
        // Чужой тип события (не order.created) → ack без обработки, чтобы не зациклить очередь.
        $ingestor = $this->fakeIngestor();
        $consumer = $this->consumerWith($ingestor);

        $result = $consumer->handle($this->envelope([
            'eventType' => 'order.status_changed',
            'idempotencyKey' => 'order.status.qr-123',
        ]));

        self::assertTrue($result, 'Чужой тип события — ack, чтобы не зациклить');
        self::assertSame(0, $ingestor->calls);
    }

    public function test_invalid_shape_is_rejected(): void
    {
        // Битая форма payload (пустой guests[]) → reject без requeue, ингестор не вызывается.
        $ingestor = $this->fakeIngestor();
        $consumer = $this->consumerWith($ingestor);

        $result = $consumer->handle($this->envelope([
            'payload' => ['order' => ['qr_order_id' => 'qr-123'], 'guests' => []],
        ]));

        self::assertFalse($result, 'Пустой guests[] → reject без requeue');
        self::assertSame(0, $ingestor->calls);
    }

    public function test_business_rejection_rolls_back_mark(): void
    {
        // Бизнес-отказ ингестора → транзакция откатывается: ключ идемпотентности НЕ занят,
        // значит событие можно прислать заново (reject без requeue для текущего сообщения).
        $ingestor = $this->fakeIngestor(reject: true);
        $consumer = $this->consumerWith($ingestor);

        $result = $consumer->handle($this->envelope());

        self::assertFalse($result);
        self::assertSame(1, $ingestor->calls);
        // Транзакция откатилась: ключ идемпотентности НЕ занят, событие можно прислать снова.
        $this->assertDatabaseMissing('processed_messages', ['idempotency_key' => 'order.qr-123']);
    }
}
