<?php

declare(strict_types=1);

namespace Tickets\EmailDelivery\Domain\ValueObject;

use InvalidArgumentException;

/**
 * Статус письма и его машина переходов (provider-ready).
 *
 *   queued ──► sending ──► sent ──► delivered ──► opened
 *      │          │          │          │
 *      └► failed ◄┘          └► bounced ◄┘     (failed/bounced ──► queued при ретрае)
 *
 * sent      — передано на SMTP-сервер (НЕ «доставлено в ящик»).
 * delivered — доставлено почтовому серверу получателя (только провайдер с вебхуками, AF-6).
 * opened    — пиксель сработал: письмо точно дошло и открыто (Ф3).
 * bounced   — отскок (только провайдер, AF-6).
 * failed    — сбой до/во время передачи на SMTP; в error — причина = «где застряло».
 *
 * Спека: .claude/specs/email-delivery-system.md (Часть 2, §4.2).
 */
final class EmailStatus
{
    public const QUEUED = 'queued';
    public const SENDING = 'sending';
    public const SENT = 'sent';
    public const DELIVERED = 'delivered';
    public const OPENED = 'opened';
    public const FAILED = 'failed';
    public const BOUNCED = 'bounced';

    /** Разрешённые переходы из каждого статуса. */
    private const TRANSITIONS = [
        self::QUEUED => [self::SENDING, self::FAILED],
        self::SENDING => [self::SENT, self::FAILED],
        self::SENT => [self::DELIVERED, self::OPENED, self::BOUNCED, self::FAILED],
        self::DELIVERED => [self::OPENED, self::BOUNCED],
        self::OPENED => [],
        self::FAILED => [self::QUEUED],
        self::BOUNCED => [self::QUEUED],
    ];

    public function __construct(private readonly string $value)
    {
        if (! isset(self::TRANSITIONS[$value])) {
            throw new InvalidArgumentException('Неизвестный статус письма: ' . $value);
        }
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    /** Можно ли перейти в целевой статус по машине состояний. */
    public function canTransitionTo(self $target): bool
    {
        return in_array($target->value, self::TRANSITIONS[$this->value], true);
    }

    /** Письмо ещё «в пути» или застряло (не финальный успех). */
    public function isUnresolved(): bool
    {
        return in_array($this->value, [self::QUEUED, self::SENDING, self::FAILED, self::BOUNCED], true);
    }

    /** @return string[] все статусы */
    public static function all(): array
    {
        return array_keys(self::TRANSITIONS);
    }
}
