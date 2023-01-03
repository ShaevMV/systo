<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Responses;

use Carbon\Carbon;
use Tickets\Order\OrderTicket\Dto\OrderTicket\OrderTicketDto;
use Tickets\Order\OrderTicket\Dto\OrderTicket\OrderTicketItemDto;
use Tickets\Shared\Domain\Bus\Query\Response;
use Tickets\Shared\Domain\Entity\AbstractionEntity;
use Tickets\Shared\Domain\ValueObject\Status;
use Tickets\Shared\Domain\ValueObject\Uuid;

class OrderTicketItem extends AbstractionEntity implements Response
{
    protected float $totalPrice = 0.00;
    protected int $count;
    protected string $humanStatus;

    public function __construct(
        protected Uuid $id,
        protected Uuid $userId,
        protected string $name,
        protected float $price,
        protected float $discount,
        protected array $guests,
        protected Status $status,
        protected Carbon $dateBuy,
        protected Carbon $dateCreate,
        protected ?string $lastComment = null,
        protected ?string $typeOfPayment = null,
        protected ?array $comment = null,
        protected ?string $email = null,
        protected ?string $promoCode = null,
    ) {
        $this->totalPrice = $price - $discount;
        $this->count = count($this->guests);
        $this->humanStatus = $this->status->getHumanStatus();
    }

    public static function fromOrderTicketDto(OrderTicketDto $orderTicketDto): self
    {
        return new self(
            $orderTicketDto->getId(),
            $orderTicketDto->getUserId(),
            $orderTicketDto->getName(),
            $orderTicketDto->getPrice()->getPrice(),
            $orderTicketDto->getPrice()->getDiscount(),
            $orderTicketDto->getGuests(),
            $orderTicketDto->getStatus(),
            $orderTicketDto->getDateBuy(),
            $orderTicketDto->getDateCreate(),
            $orderTicketDto->getLastComment(),
            $orderTicketDto->getTypesOfPaymentName(),
            $orderTicketDto->getCommentForOrder(),
            $orderTicketDto->getEmail(),
            $orderTicketDto->getPromoCode(),
        );
    }

    public function setTypeOfPayment(?string $typeOfPayment): OrderTicketItem
    {
        $this->typeOfPayment = $typeOfPayment;
        return $this;
    }

    public function setComment(?array $comment): OrderTicketItem
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
}
