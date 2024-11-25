<?php

declare(strict_types=1);

namespace Tickets\Ticket\CreateTickets\Dto;

use Carbon\Carbon;
use Shared\Domain\Entity\AbstractionEntity;
use Shared\Domain\ValueObject\Uuid;

class TicketDto extends AbstractionEntity
{
    protected Carbon $created_at;
    protected Carbon $updated_at;
    protected Uuid $id;

    public function __construct(
        protected Uuid   $order_ticket_id,
        protected string $name,
        protected Uuid   $festival_id,
        ?Uuid            $id = null,
        protected ?int   $kilter = null,
        ?Carbon          $created_at = null,
        ?Carbon          $updated_at = null,
    )
    {
        $this->id = $id ?? Uuid::random();
        $this->created_at = $created_at ?? new Carbon();
        $this->updated_at = $updated_at ?? new Carbon();
    }


    public static function fromState(array $data, Uuid $festivalId): self
    {
        return new self(
            new Uuid($data['order_ticket_id']),
            $data['name'],
            $festivalId,
            new Uuid($data['id']),
            $data['kilter'],
        );
    }

    public function getId(): Uuid
    {
        return $this->id;
    }
}
