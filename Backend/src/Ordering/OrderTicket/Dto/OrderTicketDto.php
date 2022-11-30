<?php

declare(strict_types=1);

namespace Tickets\Ordering\OrderTicket\Dto;

use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Tickets\Shared\Domain\Entity\AbstractionEntity;
use Tickets\Shared\Domain\ValueObject\Status;
use Tickets\Shared\Domain\ValueObject\Uuid;

final class OrderTicketDto extends AbstractionEntity
{
    protected Uuid $id;

    public function __construct(
        protected Uuid $user_id,
        protected string $guests,
        protected Uuid $ticket_type_id,
        protected ?string $promo_code,
        protected string $date,
        protected Uuid $types_of_payment_id,
        protected float $price,
        protected float $discount,
        protected Status $status,
        ?Uuid $id = null,
        protected ?string $name = null,
    ) {
        $this->id = $id ?? Uuid::random();
    }

    /**
     * @throws JsonException
     */
    public static function fromState(array $data): self
    {
        $id = !is_null($data['id'] ?? null) ? new Uuid($data['id']) : null;

        return new self(
            new Uuid($data['user_id']),
            is_array($data['guests']) ? Json::encode($data['guests']) : $data['guests'],
            new Uuid($data['ticket_type_id']),
            $data['promo_code'] ?? null,
            $data['date'],
            new Uuid($data['types_of_payment_id']),
            $data['price'],
            $data['discount'],
            new Status($data['status']),
            $id,
            $data['name'] ?? null,
        );
    }
}
