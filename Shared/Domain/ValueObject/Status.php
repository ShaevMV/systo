<?php

declare(strict_types=1);

namespace Shared\Domain\ValueObject;

use InvalidArgumentException;
use Shared\Domain\Entity\EntityDataInterface;

final class Status implements EntityDataInterface
{
    public const NEW = 'new';
    public const NEW_FOR_LIVE = 'new_for_live';
    public const QUESTIONNAIRE_NOT_FULL = 'questionnaire_not_full';
    public const QUESTIONNAIRE_FULL = 'questionnaire_full';
    public const PAID = 'paid';
    public const PAID_FOR_LIVE = 'paid_for_live';
    public const CANCEL = 'cancel';
    public const DIFFICULTIES_AROSE = 'difficulties_arose';
    public const LIVE_TICKET_ISSUED = 'live_ticket_issued';

    private const HUMAN_STATUS_LIST = [
        self::NEW => 'Ожидает проверки',
        self::NEW_FOR_LIVE => 'Ожидает проверки',
        self::QUESTIONNAIRE_NOT_FULL => 'Анкета не до заполнена',
        self::QUESTIONNAIRE_FULL => 'Анкета заполнена',
        self::CANCEL => 'Отменён',
        self::PAID => 'Подверждён',
        self::PAID_FOR_LIVE => 'Подверждён',
        self::DIFFICULTIES_AROSE => 'Возникли трудности',
        self::LIVE_TICKET_ISSUED => 'Выдан живой билет',
    ];

    private function getRoleChanceStatus(string $status): array
    {
        return match ($status) {
            self::NEW, self::NEW_FOR_LIVE, self::QUESTIONNAIRE_NOT_FULL, self::QUESTIONNAIRE_FULL => [
                self::CANCEL,
                self::PAID,
                self::DIFFICULTIES_AROSE,
                ],
            self::PAID, self::PAID_FOR_LIVE => [
                self::DIFFICULTIES_AROSE,
                self::LIVE_TICKET_ISSUED,
            ],
            self::DIFFICULTIES_AROSE => [
                self::CANCEL,
                self::PAID,
            ],
            self::CANCEL => [],
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

    public function isdDifficultiesArose(): bool
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
}
