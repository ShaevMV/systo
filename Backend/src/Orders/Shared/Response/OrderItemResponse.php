<?php

declare(strict_types=1);

namespace Tickets\Orders\Shared\Response;

use Carbon\Carbon;
use Shared\Domain\Bus\Query\Response;
use Shared\Domain\Entity\AbstractionEntity;
use Shared\Domain\ValueObject\Status;
use Shared\Domain\ValueObject\Uuid;
use Tickets\Order\OrderTicket\Dto\OrderTicket\GuestsDto;

/**
 * Детальный ответ по одному заказу.
 *
 * Единый Response для всех типов заказов (guest, friendly, live).
 * Используется в getItem-эндпоинтах и после смены статуса.
 */
final class OrderItemResponse extends AbstractionEntity implements Response
{
    protected string $humanStatus;
    protected int    $guestCount;

    /**
     * @param GuestsDto[] $tickets
     */
    public function __construct(
        protected Uuid    $id,
        protected int     $kilter,
        protected string  $orderType,
        protected Status  $status,
        protected array   $tickets,
        protected Uuid    $festivalId,
        protected Uuid    $userId,
        protected string  $ticketTypeName,
        protected float   $price,
        protected float   $discount,
        protected array   $availableTransitions,
        protected Carbon  $createdAt,
        protected ?string $phone             = null,
        protected ?string $promoCode         = null,
        protected ?string $typesOfPaymentName = null,
        protected ?string $userEmail         = null,
    ) {
        $this->humanStatus = $status->getHumanStatus();
        $this->guestCount  = count($tickets);
    }

    public static function fromRow(array $row, string $orderType): self
    {
        $tickets = is_array($row['ticket'])
            ? $row['ticket']
            : json_decode($row['ticket'], true);

        $guestDtos = array_map(
            fn(array $g) => GuestsDto::fromState($g),
            $tickets,
        );

        $status = new Status($row['status']);

        return new self(
            id:                  new Uuid($row['id']),
            kilter:              (int)$row['kilter'],
            orderType:           $orderType,
            status:              $status,
            tickets:             $guestDtos,
            festivalId:          new Uuid($row['festival_id']),
            userId:              new Uuid($row['user_id']),
            ticketTypeName:      $row['ticket_type_name'] ?? '',
            price:               (float)$row['price'],
            discount:            (float)($row['discount'] ?? 0),
            availableTransitions: $status->getListNextStatus(),
            createdAt:           new Carbon($row['created_at']),
            phone:               $row['phone'] ?? null,
            promoCode:           $row['promo_code'] ?? null,
            typesOfPaymentName:  $row['payment_name'] ?? null,
            userEmail:           $row['user_email'] ?? null,
        );
    }

    public function getId(): Uuid    { return $this->id; }
    public function getKilter(): int { return $this->kilter; }
    public function getUserId(): Uuid { return $this->userId; }
    public function getStatus(): Status { return $this->status; }
}
