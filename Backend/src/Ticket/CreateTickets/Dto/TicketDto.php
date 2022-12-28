<?php

namespace Tickets\Ticket\CreateTickets\Dto;

use Tickets\Shared\Domain\Entity\AbstractionEntity;
use Tickets\Shared\Domain\ValueObject\Uuid;

class TicketDto extends AbstractionEntity
{
    public function __construct(
        protected Uuid $order_ticket_id,
        protected string $name,
        protected Uuid $id,
        protected int $number = 1
    ){
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
