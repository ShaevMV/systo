<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Dto\OrderTicket;

use Shared\Domain\ValueObject\Uuid;

class QuestionnaireTicketDto
{
    protected string $link;
    public function __construct(
        protected Uuid $orderId,
        protected Uuid $ticketId,
        protected int $agy,
        protected int $howManyTimes,
        protected string $questionForSysto,
        protected string $phone,
        protected ?string $telegram = null,
        protected ?string $vk = null,
        protected ?string $musicStyles = null,
        protected ?string $name = null,
        protected ?Uuid $id = null,
        protected ?string $whereSysto = null,
        protected ?string $creationOfSisto = null,
        protected ?string $activeOfEvent = null,
    )
    {
        $this->link ='https://org.spaceofjoy.ru/questionnaire/'.$orderId->value().'/'.$ticketId->value();
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
            $data['telegram'] ?? null,
            $data['vk'] ?? null,
            $data['musicStyles'] ?? null,
            $data['name'] ?? null,
            $id,
            $data['whereSysto'] ?? null,
            $data['creationOfSisto'] ?? null,
            $data['activeOfEvent'] ?? null,

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
            'whereSysto' => $this->whereSysto,
            'creationOfSisto' => $this->creationOfSisto,
            'activeOfEvent' => $this->activeOfEvent,
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
            'name' => $this->name,
            'musicStyles' => $this->musicStyles,
            'whereSysto' => $this->whereSysto,
            'creationOfSisto' => $this->creationOfSisto,
            'activeOfEvent' => $this->activeOfEvent,
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
