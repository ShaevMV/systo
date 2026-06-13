<?php

declare(strict_types=1);

namespace Shared\Integration\Rabbit;

use InvalidArgumentException;

/**
 * EventEnvelope — нейтральный конверт межсервисного события (qr ↔ org ↔ BAZA).
 *
 * Прототип разворота архитектуры (см. `.claude/specs/qr-integration/CONTRACT_RFC_v0.md` §2).
 * Конверт намеренно язык-агностичен (чистый JSON), чтобы публиковаться и читаться
 * как PHP (org/BAZA), так и Python (qr).
 *
 * Поля:
 * - `schema_version` — semver контракта (для эволюции без слома).
 * - `event_type`     — тип события, он же routing key в RabbitMQ (`ticket.issued`, `order.paid` ...).
 * - `trace_id`       — сквозной идентификатор для трассировки воронки через все системы.
 * - `idempotency_key`— стабильный ключ для дедупликации на приёмнике (at-most-once на бизнес-эффект).
 * - `occurred_at`    — ISO8601 момент возникновения (используется для anti-replay).
 * - `source`         — система-издатель (`org` / `qr` / `baza`).
 * - `payload`        — полезная нагрузка события (произвольный ассоциативный массив).
 *
 * Подпись НЕ хранится в конверте — она вычисляется по сырому JSON тела и передаётся
 * заголовком сообщения (см. {@see EventSigner}, {@see EventPublisher}).
 */
final class EventEnvelope
{
    public const SCHEMA_VERSION = '1.0';

    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        public readonly string $eventType,
        public readonly string $traceId,
        public readonly string $idempotencyKey,
        public readonly string $occurredAt,
        public readonly string $source,
        public readonly array $payload,
        public readonly string $schemaVersion = self::SCHEMA_VERSION,
    ) {
        foreach (['eventType', 'traceId', 'idempotencyKey', 'occurredAt', 'source'] as $field) {
            if ($this->$field === '') {
                throw new InvalidArgumentException(sprintf('EventEnvelope: поле "%s" не может быть пустым', $field));
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'schema_version' => $this->schemaVersion,
            'event_type' => $this->eventType,
            'trace_id' => $this->traceId,
            'idempotency_key' => $this->idempotencyKey,
            'occurred_at' => $this->occurredAt,
            'source' => $this->source,
            'payload' => $this->payload,
        ];
    }

    /**
     * Сериализация в JSON (это ровно те байты, что подписываются и публикуются).
     */
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    /**
     * Десериализация из сырого JSON тела сообщения.
     *
     * @throws InvalidArgumentException при отсутствии обязательных ключей / битом JSON
     */
    public static function fromJson(string $json): self
    {
        try {
            /** @var array<string, mixed> $data */
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new InvalidArgumentException('EventEnvelope: тело не является валидным JSON: ' . $e->getMessage());
        }

        foreach (['event_type', 'trace_id', 'idempotency_key', 'occurred_at', 'source'] as $key) {
            if (! array_key_exists($key, $data) || ! is_string($data[$key]) || $data[$key] === '') {
                throw new InvalidArgumentException(sprintf('EventEnvelope::fromJson: отсутствует/пустой ключ "%s"', $key));
            }
        }

        $payload = $data['payload'] ?? [];
        if (! is_array($payload)) {
            throw new InvalidArgumentException('EventEnvelope::fromJson: "payload" должен быть объектом');
        }

        return new self(
            eventType: $data['event_type'],
            traceId: $data['trace_id'],
            idempotencyKey: $data['idempotency_key'],
            occurredAt: $data['occurred_at'],
            source: $data['source'],
            payload: $payload,
            schemaVersion: is_string($data['schema_version'] ?? null) ? $data['schema_version'] : self::SCHEMA_VERSION,
        );
    }
}
