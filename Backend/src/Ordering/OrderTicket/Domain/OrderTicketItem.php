<?php

declare(strict_types=1);

namespace Tickets\Ordering\OrderTicket\Domain;

use Carbon\Carbon;
use Exception;
use JsonException;
use Tickets\Ordering\OrderTicket\ValueObject\CommentForOrder;
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
        protected ?string $linkToTicket = null,
        protected ?string $typeOfPayment = null,
        protected ?array $comment = null,
        protected ?string $email = null,
        protected ?string $promoCode = null,
    ) {
        $this->totalPrice = $price - $discount;
        $this->count = count($this->guests);
        $this->humanStatus = $this->status->getHumanStatus();
    }

    /**
     * @throws JsonException
     * @throws Exception
     */
    public static function fromState(array $data): self
    {
        return new self(
            new Uuid($data['id']),
            new Uuid($data['user_id']),
            $data['name'],
            (float) $data['price'],
            (float) $data['discount'],
            json_decode($data['guests'], false, 512, JSON_THROW_ON_ERROR),
            new Status($data['status']),
            new Carbon($data['date']),
            new Carbon($data['created_at']),
            $data['last_comment'] ?? null,
            $data['linkToTicket'] ?? null,
            $data['types_of_payment_name'] ?? null,
            [],
            $data['email'] ?? null,
            $data['promo_code'] ?? null,
        );
    }

    /**
     * @throws JsonException
     */public static function fromItemOrderState(array $data): self
    {
        /** @var CommentForOrder[] $comments */
        $comments = [];
        foreach ($data['comments'] as $comment) {
            $comments[] = CommentForOrder::fromState($comment);
        }
        $data['name'] = $data['ticket_type']['name'];
        $data['last_comment'] = count($comments) > 0 ? (string)end($comments)?->getComment() : null;

        return self::fromState($data)
            ->setComment(empty($comments) ? [] : $comments)
            ->setTypeOfPayment($data['type_of_payment']['name']);
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
}
