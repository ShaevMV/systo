<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Dto\OrderTicket;

use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Shared\Domain\ValueObject\Status;
use Shared\Domain\ValueObject\Uuid;

class OrderTicketDto
{
    protected Uuid $id;

    /**
     * @param GuestsDto[] $ticket
     */
    private function __construct(
        protected Uuid     $festival_id,
        protected Uuid     $user_id,
        protected string   $email,
        protected ?string  $phone,
        protected ?Uuid    $types_of_payment_id,
        protected ?Uuid    $ticket_type_id,
        protected array    $ticket,
        protected string   $id_buy,
        protected PriceDto $priceDto,
        protected string   $datePay,
        protected ?Status  $status,
        protected ?string  $promo_code = null,
        protected bool     $is_live_ticket = false,
        protected ?Uuid    $questionnaire_type_id = null,
        ?Uuid              $id = null,
        protected ?Uuid    $inviteLink = null,
        protected ?Uuid    $friendly_id = null,
        protected ?Uuid    $location_id = null,
        protected ?Uuid    $curator_id = null,
        protected ?string  $project = null,
    )
    {
        $this->id = $id ?? Uuid::random();
    }

    /**
     * @throws JsonException
     */
    public static function fromState(
        array    $data,
        Uuid     $userId,
        PriceDto $priceDto,
        bool     $isLiveTicket = false,
        ?Uuid    $pusherId = null,
    ): self
    {
        $id = isset($data['id']) ? new Uuid($data['id']) : null;

        $status = $data['status'] ?? (!$isLiveTicket ? Status::NEW : Status::PAID_FOR_LIVE);
        $guests = is_array($data['guests']) ? $data['guests'] : Json::decode($data['guests'], 1);
        $tickets = [];
        foreach ($guests as $guest) {
            $tickets[] = GuestsDto::fromState($guest, $data['festival_id']);
        }

        return new self(
            new Uuid($data['festival_id']),
            $userId,
            $data['email'],
            $data['phone'] ?? null,
            new Uuid($data['types_of_payment_id']),
            new Uuid($data['ticket_type_id']),
            $tickets,
            $data['id_buy'] ?? '',
            $priceDto,
            $data['date'] ?? '',
            new Status($status),
            $data['promo_code'] ?? null,
            $isLiveTicket,
            empty($data['questionnaire_type_id']) ? null : new Uuid($data['questionnaire_type_id']),
            $id,
            friendly_id: $pusherId,
            location_id: empty($data['location_id']) ? null : new Uuid($data['location_id']),
            curator_id:  empty($data['curator_id'])  ? null : new Uuid($data['curator_id']),
            project:     $data['project'] ?? null,
        );
    }

    /**
     * Фабричный метод для заказа-списка (создаётся куратором).
     *
     * Отличия от обычного fromState:
     * - НЕ требует ticket_type_id, types_of_payment_id, price
     * - Обязательные: festival_id, location_id, curator_id, email получателя, гости
     * - Статус по умолчанию NEW_LIST
     *
     * @throws JsonException
     */
    public static function fromStateForList(
        array   $data,
        Uuid    $userId,      // получатель билетов
        Uuid    $curatorId,   // куратор-создатель
        Uuid    $locationId,
        ?string $project = null,
    ): self
    {
        $id = isset($data['id']) ? new Uuid($data['id']) : null;
        $status = $data['status'] ?? Status::NEW_LIST;

        $guests = is_array($data['guests']) ? $data['guests'] : Json::decode($data['guests'], 1);
        $tickets = [];
        foreach ($guests as $guest) {
            $tickets[] = GuestsDto::fromState($guest, $data['festival_id']);
        }

        return new self(
            festival_id:           new Uuid($data['festival_id']),
            user_id:               $userId,
            email:                 $data['email'],
            phone:                 $data['phone'] ?? null,
            types_of_payment_id:   null,
            ticket_type_id:        null,
            ticket:                $tickets,
            id_buy:                $data['id_buy'] ?? '',
            priceDto:              new PriceDto(0, count($tickets), 0),
            datePay:               $data['date'] ?? '',
            status:                new Status($status),
            promo_code:            null,
            is_live_ticket:        false,
            questionnaire_type_id: null,
            id:                    $id,
            inviteLink:            null,
            friendly_id:           null,
            location_id:           $locationId,
            curator_id:            $curatorId,
            project:               $project ?? ($data['project'] ?? null),
        );
    }

    /**
     * @throws JsonException
     */
    public function toArray(): array
    {
        $tickets = [];
        foreach ($this->ticket as $item) {
            $tickets[] = [
                'value' => $item->getValue(),
                'email' => $item->getEmail(),
                'number' => $item->getNumber(),
                'id' => $item->getId()->value(),
                'festival_id' => $item->getFestivalId()->value(),
            ];
        }
        $jsonTickets = Json::encode($tickets);
        return [
            'id' => $this->id,
            'festival_id' => $this->festival_id,
            'user_id' => $this->user_id,
            'ticket_type_id' => $this->ticket_type_id?->value(),
            'types_of_payment_id' => $this->types_of_payment_id?->value(),
            'guests' => $jsonTickets,
            'phone' => $this->phone,
            'price' => $this->priceDto->getPrice(),
            'discount' => $this->priceDto->getDiscount(),
            'status' => (string)$this->status,
            'date' => (string)$this->datePay,
            'promo_code' => $this->promo_code,
            'id_buy' => $this->id_buy,
            'friendly_id' => $this->friendly_id?->value(),
            'location_id' => $this->location_id?->value(),
            'curator_id'  => $this->curator_id?->value(),
            'project'     => $this->project,
        ];
    }

    public function getTicket(): array
    {
        return $this->ticket;
    }

    public function getFestivalId(): Uuid
    {
        return $this->festival_id;
    }

    public function getUserId(): Uuid
    {
        return $this->user_id;
    }

    public function getTypesOfPaymentId(): ?Uuid
    {
        return $this->types_of_payment_id;
    }

    public function getStatus(): Status
    {
        return $this->status;
    }

    public function getPromoCode(): ?string
    {
        return $this->promo_code;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getPriceDto(): PriceDto
    {
        return $this->priceDto;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getTicketTypeId(): ?Uuid
    {
        return $this->ticket_type_id;
    }

    public function getQuestionnaireTypeId(): ?Uuid
    {
        return $this->questionnaire_type_id;
    }

    public function isIsLiveTicket(): bool
    {
        return $this->is_live_ticket;
    }

    public function isBilling(): bool
    {
        return $this->types_of_payment_id !== null
            && $this->types_of_payment_id->equals(new Uuid('3fcded69-4aef-4c4a-a041-52c91e5afd91'));
    }

    public function getInviteLink(): ?Uuid
    {
        return $this->inviteLink;
    }

    public function setInviteLink(?Uuid $inviteLink): void
    {
        $this->inviteLink = $inviteLink;
    }

    public function setStatus(?Status $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getFriendlyId(): ?Uuid
    {
        return $this->friendly_id;
    }

    public function getLocationId(): ?Uuid
    {
        return $this->location_id;
    }

    public function getCuratorId(): ?Uuid
    {
        return $this->curator_id;
    }

    public function isList(): bool
    {
        return $this->curator_id !== null;
    }

    public function getProject(): ?string
    {
        return $this->project;
    }
}
