<?php

declare(strict_types=1);

namespace Shared\Integration\Rabbit;

use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

/**
 * EventPublisher — публикация подписанного события в topic-exchange RabbitMQ.
 *
 * Прототип шины qr ↔ org ↔ BAZA (см. `.claude/specs/qr-integration/CONTRACT_RFC_v0.md` §5).
 *
 * - exchange: durable topic (`systo.events` по умолчанию), routing_key = `event_type`.
 * - тело: JSON конверта {@see EventEnvelope::toJson()} (ровно те байты, что подписаны).
 * - подпись: HMAC в заголовках `x-signature` + `x-timestamp` ({@see EventSigner}).
 * - delivery_mode=2 (persistent) — сообщение переживает рестарт брокера.
 *
 * Транзакционная гарантия доставки (outbox на стороне издателя) — отдельный слой;
 * для прототипа публикуем напрямую (см. RFC, эволюция в Outbox позже).
 */
final class EventPublisher
{
    public function __construct(
        private readonly RabbitConnectionFactory $connectionFactory,
        private readonly EventSigner $signer,
        private readonly string $exchange = 'systo.events',
    ) {
    }

    /**
     * Опубликовать событие. Возвращает trace_id опубликованного сообщения.
     *
     * @param int $now текущее время (UNIX) для подписи; параметр для тестируемости
     */
    public function publish(EventEnvelope $envelope, int $now): string
    {
        $connection = $this->connectionFactory->make();
        try {
            $channel = $connection->channel();
            // durable topic exchange — идемпотентное объявление (passive=false).
            $channel->exchange_declare($this->exchange, 'topic', false, true, false);

            $body = $envelope->toJson();
            $signature = $this->signer->sign($body, $now);

            $message = new AMQPMessage($body, [
                'content_type' => 'application/json',
                'content_encoding' => 'utf-8',
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                'message_id' => $envelope->idempotencyKey,
                'timestamp' => $now,
                'application_headers' => new AMQPTable([
                    'x-signature' => $signature,
                    'x-timestamp' => (string) $now,
                    'x-idempotency-key' => $envelope->idempotencyKey,
                    'x-schema-version' => $envelope->schemaVersion,
                    'x-source' => $envelope->source,
                ]),
            ]);

            $channel->basic_publish($message, $this->exchange, $envelope->eventType);
            $channel->close();
        } finally {
            $connection->close();
        }

        return $envelope->traceId;
    }
}
