<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Dto\OrderTicket;

use Shared\Domain\ValueObject\Uuid;

class QuestionnaireTicketDto
{
    public function __construct(
        protected Uuid $orderId,
        protected int $agy,
        protected int $howManyTimes,
        protected string $questionForSysto,
        protected ?string $telegram = null,
        protected ?string $vk = null,
        protected ?string $musicStyles = null,
    )
    {
    }

    public static function fromState(array $data, Uuid $orderId): self
    {
        return new self(
            $orderId,
            (int)$data['agy'],
            (int)$data['howManyTimes'],
            $data['questionForSysto'] ?? null,
            $data['telegram'] ?? null,
            $data['vk'] ?? null,
            $data['musicStyles'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'order_id' => $this->orderId->value(),
            'agy' => $this->agy,
            'howManyTimes' => $this->howManyTimes,
            'questionForSysto' => $this->questionForSysto,
            'telegram' => $this->telegram,
            'vk' => $this->vk,
            'musicStyles' => $this->musicStyles,
        ];
    }
}
