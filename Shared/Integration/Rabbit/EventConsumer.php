<?php

declare(strict_types=1);

namespace Shared\Integration\Rabbit;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

/**
 * EventConsumer — приём, проверка подписи и доставка событий обработчику.
 *
 * Прототип шины qr ↔ org ↔ BAZA (см. `.claude/specs/qr-integration/CONTRACT_RFC_v0.md` §5–6).
 *
 * Топология: durable topic exchange → durable очередь → bind по routing-ключам.
 * QoS prefetch=1 (по одному сообщению — честная обработка). На каждое сообщение:
 *   1. достаём заголовки `x-signature` / `x-timestamp`;
 *   2. проверяем подпись по сырому телу ({@see EventSigner}); невалидная/устаревшая → reject без requeue;
 *   3. парсим конверт ({@see EventEnvelope}); битый → reject без requeue;
 *   4. вызываем $handler(EventEnvelope): bool.
 *      - true  → ack (обработано, в т.ч. «дубликат, уже обработано» — idempotency на стороне handler);
 *      - false → reject без requeue (бизнес-отказ, не зацикливаем);
 *      - исключение → nack с requeue (временный сбой — пусть повторится).
 *
 * Дедупликация (idempotency_key) — ответственность $handler (таблица processed_messages
 * на приёмнике), а не транспорта. Так Shared-слой остаётся без БД-зависимостей.
 */
final class EventConsumer
{
    public function __construct(
        private readonly RabbitConnectionFactory $connectionFactory,
        private readonly EventSigner $signer,
        private readonly string $exchange = 'systo.events',
    ) {
    }

    /**
     * Подписаться на очередь и обрабатывать сообщения.
     *
     * @param string             $queue        имя очереди (durable)
     * @param string[]           $bindingKeys  routing-ключи (`ticket.issued`, `ticket.#` ...)
     * @param callable           $handler      fn(EventEnvelope): bool
     * @param int|null           $maxMessages  остановиться после N обработанных (null — бесконечно)
     * @param int                $idleTimeout  выйти, если N секунд нет сообщений (для прототипа/тестов)
     * @param callable|null      $logger       fn(string $level, string $message): void
     */
    public function consume(
        string $queue,
        array $bindingKeys,
        callable $handler,
        ?int $maxMessages = null,
        int $idleTimeout = 0,
        ?callable $logger = null,
    ): int {
        $log = $logger ?? static fn (string $level, string $message) => null;
        $connection = $this->connectionFactory->make();
        $processed = 0;

        try {
            $channel = $connection->channel();
            $this->declareTopology($channel, $queue, $bindingKeys);
            $channel->basic_qos(0, 1, false);

            $callback = function (AMQPMessage $message) use ($handler, $log, &$processed): void {
                $body = $message->getBody();
                $headers = $this->headers($message);
                $signature = (string) ($headers['x-signature'] ?? '');
                $timestamp = (int) ($headers['x-timestamp'] ?? 0);

                if ($signature === '' || ! $this->signer->verify($body, $timestamp, $signature, time())) {
                    $log('warning', 'Отклонено: неверная подпись или устаревшее сообщение (anti-replay)');
                    $message->reject(false);

                    return;
                }

                try {
                    $envelope = EventEnvelope::fromJson($body);
                } catch (\Throwable $e) {
                    $log('warning', 'Отклонено: битый конверт — ' . $e->getMessage());
                    $message->reject(false);

                    return;
                }

                try {
                    $ok = $handler($envelope);
                } catch (\Throwable $e) {
                    // Защита от «ядовитого» сообщения: один повтор (requeue), затем reject без requeue.
                    // Иначе постоянная ошибка (например NOT NULL в БД) крутится бесконечно.
                    // В проде вместо drop — DLQ с retry-TTL (см. RFC §5), здесь — прототипный минимум.
                    if ($message->isRedelivered()) {
                        $log('error', 'Сбой обработчика повторно — reject без requeue (poison): ' . $e->getMessage());
                        $message->reject(false);
                    } else {
                        $log('error', 'Сбой обработчика, повтор (requeue once) — ' . $e->getMessage());
                        $message->nack(true);
                    }

                    return;
                }

                if ($ok) {
                    $message->ack();
                    $processed++;
                } else {
                    $log('warning', 'Бизнес-отказ обработчика, reject без requeue: ' . $envelope->idempotencyKey);
                    $message->reject(false);
                }
            };

            $channel->basic_consume($queue, '', false, false, false, false, $callback);

            while ($channel->is_consuming()) {
                if ($maxMessages !== null && $processed >= $maxMessages) {
                    break;
                }
                try {
                    $channel->wait(null, false, $idleTimeout > 0 ? $idleTimeout : 0);
                } catch (AMQPTimeoutException) {
                    break; // тишина дольше idleTimeout — выходим (для прототипа/разовых прогонов)
                }
            }

            $channel->close();
        } finally {
            $connection->close();
        }

        return $processed;
    }

    /**
     * @param string[] $bindingKeys
     */
    private function declareTopology(AMQPChannel $channel, string $queue, array $bindingKeys): void
    {
        $channel->exchange_declare($this->exchange, 'topic', false, true, false);
        $channel->queue_declare($queue, false, true, false, false);
        foreach ($bindingKeys as $key) {
            $channel->queue_bind($queue, $this->exchange, $key);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function headers(AMQPMessage $message): array
    {
        /** @var AMQPTable|null $table */
        $table = $message->has('application_headers') ? $message->get('application_headers') : null;
        if ($table instanceof AMQPTable) {
            return $table->getNativeData();
        }

        return [];
    }
}
