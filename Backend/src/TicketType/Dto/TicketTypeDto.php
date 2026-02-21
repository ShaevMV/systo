<?php

declare(strict_types=1);

namespace Tickets\TicketType\Dto;

use Carbon\Carbon;
use Shared\Domain\Bus\Query\Response;
use Shared\Domain\Entity\AbstractionEntity;
use Shared\Domain\ValueObject\Uuid;

class TicketTypeDto extends AbstractionEntity implements Response
{
    public function __construct(
        protected Uuid $id,
        protected string $name,
        protected int $price,
        protected int $sort,
        protected bool $active,
        protected bool $is_live_ticket,
        protected ?int $groupLimit = null,
        protected ?Carbon $created_at = null,
        protected ?Carbon $updated_at = null,
    )
    {
    }

    public static function fromState(array $data): self
    {
        return new self(
            empty($data['id']) ? Uuid::random() : new Uuid($data['id']),
            $data['name'],
            (int)$data['price'],
            (int)$data['sort'],
            (bool)$data['active'],
            (bool)$data['is_live_ticket'],
            $data['groupLimit'] ?? null,
            empty($data['created_at']) ? null : new Carbon($data['created_at']),
            empty($data['updated_at']) ? null : new Carbon($data['updated_at']),
        );
    }
}
