<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Dto\OrderTicket;

use Shared\Domain\ValueObject\Uuid;

class QuestionnaireTicketDto
{
    public function __construct(
        protected Uuid $orderId,
        protected Uuid $ticketId,
        protected int $agy,
        protected int $howManyTimes,
        protected string $questionForSysto,
        protected string $phone,
        protected string $link,
        protected ?string $telegram = null,
        protected ?string $vk = null,
        protected ?string $musicStyles = null,
        protected ?string $name = null,
        protected ?Uuid $id = null,
    )
    {
    }

    public static function fromState(
        array $data,
        Uuid $orderId,
        Uuid $ticketId
    ): self
    {
        $id = (empty($data['id'])) ? null : new Uuid($data['id']);
        return new self(
            $orderId,
            $ticketId,
            (int)$data['agy'],
            (int)$data['howManyTimes'],
            $data['questionForSysto'],
            $data['phone'],
            'https://org.spaceofjoy.ru/'.$orderId->value().'/'.$ticketId->value(),
            $data['telegram'] ?? null,
            $data['vk'] ?? null,
            $data['musicStyles'] ?? null,
            $data['name'] ?? null,
            $id,
        );
    }

    public function toArray(): array
    {
        return [
            'order_id' => $this->orderId->value(),
            'ticket_id' => $this->ticketId->value(),
            'agy' => $this->agy,
            'howManyTimes' => $this->howManyTimes,
            'questionForSysto' => $this->questionForSysto,
            'link' => $this->link,
            'phone' => $this->phone,
            'telegram' => $this->telegram,
            'vk' => $this->vk,
            'musicStyles' => $this->musicStyles,
            'name' => $this->name,
        ];
    }

    public function toArrayForMySql(): array
    {
        return [
            'order_id' => $this->orderId->value(),
            'ticket_id' => $this->ticketId->value(),
            'agy' => $this->agy,
            'howManyTimes' => $this->howManyTimes,
            'questionForSysto' => $this->questionForSysto,
            'phone' => $this->phone,
            'telegram' => $this->telegram,
            'vk' => $this->vk,
            'musicStyles' => $this->musicStyles,
        ];
    }

    public function getOrderId(): Uuid
    {
        return $this->orderId;
    }

    public function getTicketId(): Uuid
    {
        return $this->ticketId;
    }
}
