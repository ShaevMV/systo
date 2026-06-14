<?php

declare(strict_types=1);

namespace Tickets\Integration\Qr;

use Illuminate\Support\Facades\DB;
use Psr\Log\LoggerInterface;
use Shared\Integration\Rabbit\EventEnvelope;
use Tickets\Integration\Qr\Dto\ProcessedMessageDto;
use Tickets\Integration\Qr\Exception\QrOrderRejectedException;
use Tickets\Integration\Qr\Repositories\ProcessedMessageRepositoryInterface;

/**
 * Обработчик входящих событий заказа от qr.spaceofjoy.ru (qr → шина → org).
 *
 * Транспорт, проверку подписи и anti-replay делает EventConsumer (Shared). Этот класс — бизнес-слой
 * приёма: проверка версии схемы, дедупликация (at-most-once) и делегирование ингестору
 * (см. CONTRACT_RFC_v0.md §7). Возврат bool интерпретируется EventConsumer:
 *   - true       → ack (обработано / дубликат / не наш тип);
 *   - false      → reject без requeue (перманентный бизнес-отказ, не зацикливаем);
 *   - исключение → nack с requeue (транзиентный сбой — пусть повторится, затем poison).
 *
 * Дедуп — mark-first внутри транзакции: ключ идемпотентности занимается ДО бизнес-эффекта,
 * поэтому гонка двух воркеров не создаст дубль заказа (UNIQUE на idempotency_key).
 */
final class QrOrderConsumer
{
    /** Поддерживаемая мажорная версия схемы контракта (CONTRACT_RFC_v0.md §12). */
    public const SUPPORTED_SCHEMA_MAJOR = 1;

    /** Тип события, который обрабатываем в этом потоке (Ф1). order.status_changed — Ф3+. */
    public const HANDLED_EVENT_TYPE = 'order.created';

    public function __construct(
        private readonly ProcessedMessageRepositoryInterface $processed,
        private readonly QrOrderIngestorInterface $ingestor,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function handle(EventEnvelope $envelope): bool
    {
        $context = [
            'trace_id' => $envelope->traceId,
            'idempotency_key' => $envelope->idempotencyKey,
            'event_type' => $envelope->eventType,
            'source' => $envelope->source,
        ];

        if ($envelope->eventType !== self::HANDLED_EVENT_TYPE) {
            // Очередь привязана к order.created, но защищаемся: чужой тип — ack, чтобы не зациклить.
            $this->logger->warning('[qr-ingest] Пропущен неожиданный тип события', $context);

            return true;
        }

        if (! $this->isSchemaSupported($envelope->schemaVersion)) {
            $this->logger->error('[qr-ingest] Несовместимая версия схемы — reject без requeue', $context + [
                'schema_version' => $envelope->schemaVersion,
                'supported_major' => self::SUPPORTED_SCHEMA_MAJOR,
            ]);

            return false;
        }

        if (! $this->hasValidShape($envelope->payload)) {
            $this->logger->error('[qr-ingest] Невалидная форма payload (нет order/guests) — reject без requeue', $context);

            return false;
        }

        // Быстрый путь: известный дубликат — ack без открытия транзакции.
        if ($this->processed->isProcessed($envelope->idempotencyKey)) {
            $this->logger->info('[qr-ingest] Дубликат (уже обработано) — ack', $context);

            return true;
        }

        try {
            DB::transaction(function () use ($envelope): void {
                // Занимаем ключ идемпотентности ДО бизнес-эффекта (защита от гонки воркеров).
                $this->processed->markProcessed(new ProcessedMessageDto(
                    idempotencyKey: $envelope->idempotencyKey,
                    eventType: $envelope->eventType,
                    source: $envelope->source,
                    traceId: $envelope->traceId,
                ));

                $this->ingestor->ingest($envelope);
            });
        } catch (QrOrderRejectedException $e) {
            // Перманентный бизнес-отказ: транзакция откатилась (ключ не занят), reject без requeue.
            $this->logger->warning('[qr-ingest] Бизнес-отказ приёма заказа — reject без requeue: ' . $e->getMessage(), $context);

            return false;
        }
        // Прочие исключения (БД недоступна, дубль-ключ от гонки) бубблятся в EventConsumer → requeue.

        $this->logger->info('[qr-ingest] Заказ принят и обработан', $context);

        return true;
    }

    private function isSchemaSupported(string $schemaVersion): bool
    {
        $major = (int) explode('.', $schemaVersion)[0];

        return $major === self::SUPPORTED_SCHEMA_MAJOR;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function hasValidShape(array $payload): bool
    {
        return isset($payload['order'], $payload['guests'])
            && is_array($payload['order'])
            && is_array($payload['guests'])
            && $payload['guests'] !== [];
    }
}
