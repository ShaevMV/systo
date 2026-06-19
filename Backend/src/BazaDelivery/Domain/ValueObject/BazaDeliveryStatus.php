<?php

declare(strict_types=1);

namespace Tickets\BazaDelivery\Domain\ValueObject;

use InvalidArgumentException;

/**
 * Статус доставки билета в Baza («система входа») и его машина переходов.
 *
 *   queued ──► sending ──► delivered
 *      │          │
 *      └► failed ◄┘          (failed ──► queued при ретрае/ручном повторе)
 *
 * queued    — постановка в очередь на запись в Baza (BazaDeliveryDispatcher).
 * sending   — задача DeliverTicketToBazaJob начала запись (attempts++).
 * delivered — билет записан в Baza (setInBaza/setInBazaList/setInBazaLive/setInBazaAuto = true).
 * failed    — сбой записи; в error — причина = «где застряло».
 *
 * Зеркало EmailStatus (без delivered/opened-провайдерских веток — Baza либо записан, либо нет).
 * Спека: .claude/specs/baza-delivery-async-prompt.md (§3.1).
 */
final class BazaDeliveryStatus
{
    public const QUEUED = 'queued';
    public const SENDING = 'sending';
    public const DELIVERED = 'delivered';
    public const FAILED = 'failed';

    /** Разрешённые переходы из каждого статуса. */
    private const TRANSITIONS = [
        self::QUEUED => [self::SENDING, self::FAILED],
        self::SENDING => [self::DELIVERED, self::FAILED],
        self::DELIVERED => [],
        self::FAILED => [self::QUEUED],
    ];

    public function __construct(private readonly string $value)
    {
        if (! isset(self::TRANSITIONS[$value])) {
            throw new InvalidArgumentException('Неизвестный статус доставки в Baza: ' . $value);
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

    /** Доставка ещё «в пути» или застряла (не финальный успех). */
    public function isUnresolved(): bool
    {
        return in_array($this->value, [self::QUEUED, self::SENDING, self::FAILED], true);
    }

    /** @return string[] все статусы */
    public static function all(): array
    {
        return array_keys(self::TRANSITIONS);
    }
}
