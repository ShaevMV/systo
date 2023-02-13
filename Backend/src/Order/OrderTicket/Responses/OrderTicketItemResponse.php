<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Responses;

use Carbon\Carbon;
use Nette\Utils\Json;
use Tickets\Order\OrderTicket\Domain\OrderTicketDto;
use Tickets\Shared\Domain\Bus\Query\Response;
use Tickets\Shared\Domain\Entity\AbstractionEntity;
use Tickets\Shared\Domain\ValueObject\Status;
use Tickets\Shared\Domain\ValueObject\Uuid;
use Tickets\Ticket\CreateTickets\Dto\TicketDto;

class OrderTicketItemResponse extends AbstractionEntity implements Response
{
    protected float $totalPrice = 0.00;
    protected int $count;
    protected string $humanStatus;

    /**
     * @param  Uuid  $id
     * @param  Uuid  $userId
     * @param  int  $kilter
     * @param  string  $name
     * @param  float  $price
     * @param  float  $discount
     * @param  array  $guests
     * @param  Status  $status
     * @param  Carbon  $dateBuy
     * @param  Carbon  $dateCreate
     * @param  string  $typeOfPayment
     * @param  string  $email
     * @param  TicketDto[]  $tickets
     * @param  string|null  $promoCode
     */
    public function __construct(
        protected Uuid $id,
        protected Uuid $userId,
        protected int $kilter,
        protected string $name,
        protected float $price,
        protected float $discount,
        protected array $guests,
        protected Status $status,
        protected Carbon $dateBuy,
        protected Carbon $dateCreate,
        protected string $typeOfPayment,
        protected string $email,
        protected array $tickets,
        protected ?string $promoCode = null,
    ) {
        $this->totalPrice = $price - $discount;
        $this->count = count($this->guests);
        $this->humanStatus = $this->status->getHumanStatus();
    }

    public static function fromState(array $data): self
    {
        $guests = is_array($data['guests']) ? $data['guests'] : Json::decode($data['guests'], 1);
        $tickets = [];
        foreach ($data['tickets'] as $ticket) {
            $tickets[] = TicketDto::fromState($ticket);
        }

        return new self(
            new Uuid($data['id']),
            new Uuid($data['user_id']),
            $data['kilter'],
            $data['ticket_type']['name'],
            $data['price'],
            $data['discount'],
            $guests,
            new Status($data['status']),
            new Carbon($data['date']),
            new Carbon($data['created_at']),
            $data['type_of_payment']['name'],
            $data['users']['email'],
            $tickets,
            $data['promo_code']
        );
    }

    public function setTypeOfPayment(?string $typeOfPayment): OrderTicketItemResponse
    {
        $this->typeOfPayment = $typeOfPayment;
        return $this;
    }

    public function setComment(?array $comment): OrderTicketItemResponse
    {
        $this->comment = $comment;
        return $this;
    }

    public function getUserId(): Uuid
    {
        return $this->userId;
    }

    public function getGuests(): array
    {
        return $this->guests;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getKilter(): int
    {
        return $this->kilter;
    }
}
