<?php

declare(strict_types=1);

namespace Tickets\TicketTypePrice\Dto;

use Carbon\Carbon;
use Shared\Domain\Bus\Query\Response;
use Shared\Domain\Entity\AbstractionEntity;
use Shared\Domain\ValueObject\Uuid;

class TicketTypePriceDto extends AbstractionEntity implements Response
{
    public function __construct(
        protected Uuid    $id,
        protected Uuid    $ticket_type_id,
        protected float   $price,
        protected Carbon  $before_date,
        protected ?Carbon $created_at = null,
        protected ?Carbon $updated_at = null,
    ) {
    }

    public static function fromState(array $data): self
    {
        return new self(
            empty($data['id']) ? Uuid::random() : new Uuid($data['id']),
            new Uuid($data['ticket_type_id']),
            (float) $data['price'],
            new Carbon($data['before_date']),
            empty($data['created_at']) ? null : new Carbon($data['created_at']),
            empty($data['updated_at']) ? null : new Carbon($data['updated_at']),
        );
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getTicketTypeId(): Uuid
    {
        return $this->ticket_type_id;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function getBeforeDate(): Carbon
    {
        return $this->before_date;
    }
}
