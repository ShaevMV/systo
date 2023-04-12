<?php

declare(strict_types=1);

namespace Tickets\Ticket\CreateTickets\Dto;

use Illuminate\Support\Carbon;
use Tickets\Shared\Domain\Entity\AbstractionEntity;
use Tickets\Shared\Domain\ValueObject\Status;
use Tickets\Shared\Domain\ValueObject\Uuid;

class PushTicketsDto extends AbstractionEntity
{
    protected Carbon $created_at;
    protected Carbon $updated_at;

    public function __construct(
        protected int $kilter,
        protected Uuid $uuid,
        protected string $name,
        protected string $email,
        protected string $phone,
        protected Status $status,
        protected Carbon $date_order,
    ){
        $this->created_at = Carbon::now();
        $this->updated_at = Carbon::now();
    }

    public static function fromState(array $data): self
    {
        return new self(
            $data['kilter'],
            new Uuid($data['id']),
            $data['name'],
            $data['email'],
            $data['phone'],
            new Status($data['status']),
            Carbon::parse($data['created_at']),
        );
    }

    public function getUuid(): Uuid
    {
        return $this->uuid;
    }
}
