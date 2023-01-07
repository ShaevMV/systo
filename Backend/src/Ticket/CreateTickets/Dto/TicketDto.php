<?php

declare(strict_types = 1);

namespace Tickets\Ticket\CreateTickets\Dto;

use Carbon\Carbon;
use Tickets\Shared\Domain\Entity\AbstractionEntity;
use Tickets\Shared\Domain\ValueObject\Uuid;

class TicketDto extends AbstractionEntity
{
    protected Carbon $created_at;
    protected Carbon $updated_at;

    public function __construct(
        protected Uuid $order_ticket_id,
        protected string $name,
        protected Uuid $id,
        protected int $number = 1,
        ?Carbon $created_at = null,
        ?Carbon $updated_at = null,
    ){
        $this->created_at = $created_at ?? new Carbon();
        $this->updated_at = $updated_at ?? new Carbon();
    }


    public static function fromState(array $data): self
    {
        return new self(
            $data['order_id'],
            $data['name'],
            $data['id']
        );
    }
}
