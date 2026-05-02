<?php

declare(strict_types=1);

namespace Tickets\Orders\Shared\Response;

use Carbon\Carbon;
use Shared\Domain\Bus\Query\Response;
use Shared\Domain\Entity\AbstractionEntity;
use Shared\Domain\ValueObject\Status;
use Shared\Domain\ValueObject\Uuid;

/**
 * Строка заказа в списке (getList / getUserList).
 *
 * Единый Response для всех типов заказов.
 * Содержит только поля необходимые для отображения в таблице.
 */
final class OrderListItemResponse extends AbstractionEntity implements Response
{
    protected string $humanStatus;

    public function __construct(
        protected Uuid    $id,
        protected int     $kilter,
        protected string  $orderType,
        protected Status  $status,
        protected int     $guestCount,
        protected string  $ticketTypeName,
        protected float   $price,
        protected float   $discount,
        protected array   $availableTransitions,
        protected Carbon  $createdAt,
        protected ?string $userEmail = null,
        protected ?string $phone     = null,
        protected ?string $promoCode = null,
    ) {
        $this->humanStatus = $status->getHumanStatus();
    }

    public static function fromRow(array $row, string $orderType): self
    {
        $tickets = is_array($row['ticket'])
            ? $row['ticket']
            : json_decode($row['ticket'], true);

        $status = new Status($row['status']);

        return new self(
            id:                   new Uuid($row['id']),
            kilter:               (int)$row['kilter'],
            orderType:            $orderType,
            status:               $status,
            guestCount:           count($tickets),
            ticketTypeName:       $row['ticket_type_name'] ?? '',
            price:                (float)$row['price'],
            discount:             (float)($row['discount'] ?? 0),
            availableTransitions: $status->getListNextStatus(),
            createdAt:            new Carbon($row['created_at']),
            userEmail:            $row['user_email'] ?? null,
            phone:                $row['phone'] ?? null,
            promoCode:            $row['promo_code'] ?? null,
        );
    }

    public function getId(): Uuid    { return $this->id; }
    public function getKilter(): int { return $this->kilter; }
    public function getStatus(): Status { return $this->status; }
}
