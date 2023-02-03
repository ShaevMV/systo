<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Domain;

use Illuminate\Support\Carbon;
use Nette\Utils\Json;
use Tickets\Order\OrderTicket\Dto\OrderTicket\PriceDto;
use Tickets\Shared\Domain\ValueObject\Status;
use Tickets\Shared\Domain\ValueObject\Uuid;

class OrderTicketDto
{
    protected Uuid $id;

    public function __construct(
        protected Uuid $festival_id,
        protected Uuid $user_id,
        protected string $email,
        protected string $phone,
        protected Uuid $types_of_payment_id,
        protected Uuid $ticket_type_id,
        protected array $ticket,
        protected string $id_buy,
        protected PriceDto $priceDto,
        protected Carbon $datePay,
        protected ?Status $status,
        protected ?string $promo_code = null,
        ?Uuid $id = null,
    ) {
        $this->id = $id ?? Uuid::random();
    }

    public static function fromState(
        array $data,
        Uuid $userId,
        PriceDto $priceDto
    ): self {
        $id = isset($data['id']) ? new Uuid($data['id']) : null;
        $status = $data['status'] ?? Status::NEW;
        $guests = is_array($data['guests']) ? $data['guests'] : Json::decode($data['guests'],1);

        return new self(
            new Uuid($data['festival_id']),
            $userId,
            $data['email'],
            $data['phone'],
            new Uuid($data['types_of_payment_id']),
            new Uuid($data['ticket_type_id']),
            $guests,
            $data['id_buy'],
            $priceDto,
            new Carbon($data['date']),
            new Status($status),
            $data['promo_code'],
            $id
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'festival_id' => $this->festival_id,
            'user_id' => $this->user_id,
            'ticket_type_id' => $this->ticket_type_id,
            'types_of_payment_id' => $this->types_of_payment_id,
            'guests' => Json::encode($this->ticket),
            'phone' => $this->phone,
            'price' => $this->priceDto->getPrice(),
            'discount' => $this->priceDto->getDiscount(),
            'status' => (string)$this->status,
            'date' => (string)$this->datePay,
            'promo_code' => $this->promo_code,
            'id_buy' => $this->id_buy,
        ];
    }

    public function getTicket(): array
    {
        return $this->ticket;
    }

    /**
     * @return Uuid
     */
    public function getFestivalId(): Uuid
    {
        return $this->festival_id;
    }

    /**
     * @return Uuid
     */
    public function getUserId(): Uuid
    {
        return $this->user_id;
    }

    /**
     * @return Uuid
     */
    public function getTypesOfPaymentId(): Uuid
    {
        return $this->types_of_payment_id;
    }

    /**
     * @return Status
     */
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

    /**
     * @return PriceDto
     */
    public function getPriceDto(): PriceDto
    {
        return $this->priceDto;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }
}
