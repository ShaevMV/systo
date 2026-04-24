<?php

declare(strict_types=1);

namespace Shared\Domain\ValueObject;

use InvalidArgumentException;
use Shared\Domain\Entity\EntityDataInterface;

final class Status implements EntityDataInterface
{
    public const NEW = 'new';
    public const NEW_FOR_LIVE = 'new_for_live';
    public const NEW_FOR_LIST = 'new_for_list';
    public const PAID = 'paid';
    public const PAID_FOR_LIVE = 'paid_for_live';
    public const CANCEL = 'cancel';
    public const CANCEL_FOR_LIVE = 'cancel_for_live';
    public const DIFFICULTIES_AROSE = 'difficulties_arose';
    public const LIVE_TICKET_ISSUED = 'live_ticket_issued';
    public const PENDING_CURATOR = 'pending_curator';

    private const HUMAN_STATUS_LIST = [
        self::NEW => 'Ожидает проверки',
        self::NEW_FOR_LIVE => 'Ожидает проверки',
        self::NEW_FOR_LIST => 'Список куратора',
        self::CANCEL => 'Отменён',
        self::CANCEL_FOR_LIVE => 'Отменён',
        self::PAID => 'Подверждён',
        self::PAID_FOR_LIVE => 'Подверждён',
        self::DIFFICULTIES_AROSE => 'Возникли трудности',
        self::LIVE_TICKET_ISSUED => 'Выдан живой билет',
        self::PENDING_CURATOR => 'Ожидает модерации',
    ];

    private function getRoleChanceStatus(string $status): array
    {
        return match ($status) {
            self::NEW => [
                self::CANCEL,
                self::PAID,
                self::DIFFICULTIES_AROSE,
            ],
            self::NEW_FOR_LIVE => [
                self::CANCEL,
                self::PAID_FOR_LIVE,
                self::DIFFICULTIES_AROSE,
                self::LIVE_TICKET_ISSUED
            ],
            self::PAID => [
                self::DIFFICULTIES_AROSE,
            ],
            self::PAID_FOR_LIVE => [
                self::LIVE_TICKET_ISSUED,
                self::CANCEL_FOR_LIVE,
            ],
            self::DIFFICULTIES_AROSE => [
                self::CANCEL,
                self::PAID,
            ],
            self::LIVE_TICKET_ISSUED => [
                self::CANCEL_FOR_LIVE,
            ],
            self::NEW_FOR_LIST => [
                self::PENDING_CURATOR,
                self::CANCEL,
            ],
            self::PENDING_CURATOR => [
                self::PAID,
                self::CANCEL,
                self::DIFFICULTIES_AROSE,
            ],
            self::CANCEL, self::CANCEL_FOR_LIVE => []
        };
    }

    public function __construct(
        private string $name
    )
    {
        $this->isCorrect($this->name);
    }

    private function isCorrect(string $name): void
    {
        if (!array_key_exists($name, self::HUMAN_STATUS_LIST)) {
            throw new InvalidArgumentException(sprintf('<%s> does not allow the value <%s>.', self::class, $name));
        }
    }

    public function __toString(): string
    {
        return $this->name;
    }

    public function toJson(): string
    {
        $result = $this->name;
        return $this->name;
    }

    public function getHumanStatus(): string
    {
        return self::HUMAN_STATUS_LIST[$this->name];
    }

    public function isCorrectNextStatus(Status $nextStatus): bool
    {
        $strNextStatus = (string)$nextStatus;
        $role = self::getRoleChanceStatus($this->name);

        return in_array($strNextStatus, $role, true);
    }

    public function getListNextStatus(): array
    {
        $result = [];

        foreach (self::getRoleChanceStatus($this->name) as $status) {
            $result[$status] = (new self($status))->getHumanStatus();
        }

        return $result;
    }

    public function isPaid(): bool
    {
        return $this->name === self::PAID;
    }

    public function isCancel(): bool
    {
        return $this->name === self::CANCEL;
    }

    public function isDifficultiesArose(): bool
    {
        return $this->name === self::DIFFICULTIES_AROSE;
    }

    public function isLiveIssued(): bool
    {
        return $this->name === self::LIVE_TICKET_ISSUED;
    }

    public function isNewForLive(): bool
    {
        return $this->name === self::NEW_FOR_LIVE;
    }

    public function isPaidForLive(): bool
    {
        return $this->name === self::PAID_FOR_LIVE;
    }

    public function isNewForList(): bool
    {
        return $this->name === self::NEW_FOR_LIST;
    }

    public function isPendingCurator(): bool
    {
        return $this->name === self::PENDING_CURATOR;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function equals(Status $other): bool
    {
        return $this->name === $other->name;
    }
}
