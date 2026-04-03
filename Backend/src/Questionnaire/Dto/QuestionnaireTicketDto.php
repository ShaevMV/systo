<?php

declare(strict_types=1);

namespace Tickets\Questionnaire\Dto;

use Shared\Domain\Bus\Query\Response;
use Shared\Domain\ValueObject\Uuid;
use Tickets\Questionnaire\Domain\ValueObject\QuestionnaireStatus;

class QuestionnaireTicketDto implements Response
{
    protected ?string $link;
    public function __construct(
        protected int                 $agy,
        protected string              $questionForSysto,
        protected string              $phone,
        protected ?string              $howManyTimes = null,
        protected string              $status = QuestionnaireStatus::NEW,
        protected bool                $is_have_in_club = false,
        protected ?string             $email = null,
        protected ?string             $telegram = null,
        protected ?string             $vk = null,
        protected ?string             $musicStyles = null,
        protected ?string             $name = null,
        protected ?string             $whereSysto = null,
        protected ?string             $creationOfSisto = null,
        protected ?string             $activeOfEvent = null,
        protected ?Uuid               $userId = null,
        protected ?Uuid $orderId = null,
        protected ?Uuid $ticketId = null,
        protected ?int $id = null,
    )
    {
        $this->link = $this->getLink();
    }

    public static function fromState(
        array $data,
    ): self
    {
        $id = (empty($data['id'])) ? null : (int) $data['id'];
        $userId = (empty($data['user_id'])) ? null : new Uuid($data['user_id']);
        $ticketId = (empty($data['ticket_id'] ?? null)) ? null : new Uuid($data['ticket_id']);
        $orderId = (empty($data['order_id'] ?? null)) ? null : new Uuid($data['order_id']);

        $jsonData = $data['data'] ?? [];
        if (is_string($jsonData)) {
            $jsonData = json_decode($jsonData, true) ?? [];
        }

        return new self(
            (int)($jsonData['agy'] ?? $data['agy'] ?? 0),
            $jsonData['questionForSysto'] ?? $data['questionForSysto'] ?? '',
            $jsonData['phone'] ?? $data['phone'] ?? '',
            (string)($jsonData['howManyTimes'] ?? $data['howManyTimes'] ?? null),
            $data['status'] ?? QuestionnaireStatus::NEW,
            (bool)($jsonData['is_have_in_club'] ?? $data['is_have_in_club'] ?? false),
            $jsonData['email'] ?? $data['email'] ?? null,
            $jsonData['telegram'] ?? $data['telegram'] ?? null,
            $jsonData['vk'] ?? $data['vk'] ?? null,
            $jsonData['musicStyles'] ?? $data['musicStyles'] ?? null,
            $jsonData['name'] ?? $data['name'] ?? null,
            $jsonData['whereSysto'] ?? $data['whereSysto'] ?? null,
            $jsonData['creationOfSisto'] ?? $data['creationOfSisto'] ?? null,
            $jsonData['activeOfEvent'] ?? $data['activeOfEvent'] ?? null,
            $userId,
            $orderId,
            $ticketId,
            $id,
        );
    }

    public function toArray(): array
    {
        return [
            'agy' => $this->agy,
            'howManyTimes' => $this->howManyTimes,
            'questionForSysto' => $this->questionForSysto,
            'link' => $this->link,
            'phone' => $this->phone,
            'status' => $this->status,
            'is_have_in_club' => $this->is_have_in_club,
            'email' => $this->email,
            'telegram' => $this->telegram,
            'vk' => $this->vk,
            'name' => $this->name,
            'musicStyles' => $this->musicStyles,
            'whereSysto' => $this->whereSysto,
            'creationOfSisto' => $this->creationOfSisto,
            'activeOfEvent' => $this->activeOfEvent,
            'user_id' => $this->userId?->value(),
            'order_id' => $this->orderId?->value(),
            'ticket_id' => $this->ticketId?->value(),
            'id' => $this->id,
            'message' => '',
        ];
    }

    public function toArrayForMySql(): array
    {
        $dataFields = [
            'agy' => $this->agy,
            'howManyTimes' => $this->howManyTimes,
            'questionForSysto' => $this->questionForSysto,
            'phone' => $this->phone,
            'is_have_in_club' => $this->is_have_in_club,
            'email' => $this->email,
            'telegram' => $this->telegram,
            'vk' => $this->vk,
            'name' => $this->name,
            'musicStyles' => $this->musicStyles,
            'whereSysto' => $this->whereSysto,
            'creationOfSisto' => $this->creationOfSisto,
            'activeOfEvent' => $this->activeOfEvent,
        ];

        return [
            'data' => json_encode($dataFields),
            'order_id' => $this->orderId?->value(),
            'ticket_id' => $this->ticketId?->value(),
            'user_id' => $this->userId?->value(),
            'status' => $this->status ?? QuestionnaireStatus::APPROVE,
        ];
    }

    public function getOrderId(): ?Uuid
    {
        return $this->orderId;
    }

    public function getTicketId(): ?Uuid
    {
        return $this->ticketId;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getLink(): ?string
    {
        $host = \App::isLocal() ? 'http://org.tickets.loc/' : 'https://org.spaceofjoy.ru/';

        if($this->ticketId?->value() && $this->orderId?->value()) {
            return $host.'questionnaire/quest/'.$this->ticketId->value().'/'.$this->orderId->value();
        }

        return $this->id ? $host.'questionnaire/newUser/' : null;
    }

    public function setUserId(?Uuid $userId): void
    {
        $this->userId = $userId;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getTelegram(): ?string
    {
        return $this->telegram;
    }

    public function getId(): ?int
    {
        return $this->id;
    }
}
