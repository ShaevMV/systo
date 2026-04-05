<?php

declare(strict_types=1);

namespace Tickets\Questionnaire\Dto;

use Shared\Domain\Bus\Query\Response;
use Shared\Domain\ValueObject\Uuid;
use Tickets\Questionnaire\Domain\ValueObject\QuestionnaireStatus;

class QuestionnaireTicketDto implements Response
{
    protected ?string $link;
    protected array $extraData = [];

    public function __construct(
        protected ?int                $agy = null,
        protected ?string             $questionForSysto = null,
        protected ?string             $phone = null,
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
        protected ?Uuid $questionnaireTypeId = null,
    )
    {
        $this->link = $this->getLink();
    }

    public function setExtraData(array $data): void
    {
        $this->extraData = $data;
    }

    public static function fromState(
        array $data,
    ): self
    {
        $id = (empty($data['id'])) ? null : (int) $data['id'];
        $userId = (empty($data['user_id'])) ? null : new Uuid($data['user_id']);
        $ticketId = (empty($data['ticket_id'] ?? null)) ? null : new Uuid($data['ticket_id']);
        $orderId = (empty($data['order_id'] ?? null)) ? null : new Uuid($data['order_id']);
        $questionnaireTypeId = (empty($data['questionnaire_type_id'])) ? null : new Uuid($data['questionnaire_type_id']);

        $jsonData = $data['data'] ?? [];
        if (is_string($jsonData)) {
            $jsonData = json_decode($jsonData, true) ?? [];
        }

        // Собираем динамические поля (детская анкета и др.)
        $knownFields = ['agy', 'questionForSysto', 'phone', 'howManyTimes', 'is_have_in_club',
            'email', 'telegram', 'vk', 'musicStyles', 'name', 'whereSysto',
            'creationOfSisto', 'activeOfEvent'];
        $reservedRootKeys = array_merge($knownFields, [
            'id', 'user_id', 'ticket_id', 'order_id', 'status',
            'questionnaire_type_id', 'data',
        ]);
        $extraData = [];

        // Собираем из JSON data
        if (is_array($jsonData)) {
            foreach ($jsonData as $key => $value) {
                if (!in_array($key, $knownFields, true)) {
                    $extraData[$key] = $value;
                }
            }
        }

        // Собираем из корневого $data — все поля кроме зарезервированных
        foreach ($data as $key => $value) {
            if (!in_array($key, $reservedRootKeys, true) && !isset($extraData[$key])) {
                $extraData[$key] = $value;
            }
        }

        $instance = new self(
            empty($jsonData['agy'] ?? $data['agy'] ?? null) ? null : (int)($jsonData['agy'] ?? $data['agy']),
            $jsonData['questionForSysto'] ?? $data['questionForSysto'] ?? null,
            $jsonData['phone'] ?? $data['phone'] ?? null,
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
            $questionnaireTypeId,
        );

        if (!empty($extraData)) {
            $instance->setExtraData($extraData);
        }

        return $instance;
    }

    public function toArray(): array
    {
        $result = [
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
            'questionnaire_type_id' => $this->questionnaireTypeId?->value(),
            'message' => '',
        ];

        // Добавляем динамические поля (детская анкета и др.)
        foreach ($this->extraData as $key => $value) {
            $result[$key] = $value;
        }

        return $result;
    }

    public function toArrayForMySql(): array
    {
        // Стандартные поля имеют приоритет над extraData
        $dataFields = [];
        foreach ($this->extraData as $key => $value) {
            $dataFields[$key] = $value;
        }
        $dataFields['agy'] = $this->agy;
        $dataFields['howManyTimes'] = $this->howManyTimes;
        $dataFields['questionForSysto'] = $this->questionForSysto;
        $dataFields['phone'] = $this->phone;
        $dataFields['is_have_in_club'] = $this->is_have_in_club;
        $dataFields['vk'] = $this->vk;
        $dataFields['name'] = $this->name;
        $dataFields['musicStyles'] = $this->musicStyles;
        $dataFields['whereSysto'] = $this->whereSysto;
        $dataFields['creationOfSisto'] = $this->creationOfSisto;
        $dataFields['activeOfEvent'] = $this->activeOfEvent;

        return [
            'data' => json_encode($dataFields, JSON_UNESCAPED_UNICODE),
            'email' => $this->email,
            'telegram' => $this->telegram,
            'order_id' => $this->orderId?->value(),
            'ticket_id' => $this->ticketId?->value(),
            'user_id' => $this->userId?->value(),
            'status' => $this->status ?? QuestionnaireStatus::APPROVE,
            'questionnaire_type_id' => $this->questionnaireTypeId?->value(),
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
