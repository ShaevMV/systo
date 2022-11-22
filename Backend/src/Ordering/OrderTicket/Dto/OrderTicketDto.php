<?php

declare(strict_types=1);

namespace Tickets\Ordering\OrderTicket\Dto;

use Tickets\Shared\Domain\Entity\AbstractionEntity;
use Tickets\Shared\Domain\ValueObject\Uuid;

final class OrderTicketDto extends AbstractionEntity
{
    protected Uuid $id;

    public function __construct(
        protected Uuid $user_id,
        protected array $guests,
        protected Uuid $ticket_type_id,
        protected ?string $promo_code,
        protected string $date,
        protected Uuid $types_of_payment_id,
        ?Uuid $id = null,
    ) {
        $this->id = $id ?? Uuid::random();
    }

    public static function fromState(array $data): self
    {
        $id = !is_null($data['id']) ? new Uuid($data['id']) : null;

        return new self(
            new Uuid($data['user_id']),
            $data['guests'],
            new Uuid($data['ticket_type_id']),
            $data['promo_code'] ?? null,
            $data['date'],
            new Uuid($data['types_of_payment_id']),
            $id
        );
    }
}
