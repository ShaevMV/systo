<?php

declare(strict_types=1);

namespace Tickets\Ordering\OrderTicket\Domain;

use Carbon\Carbon;
use JsonException;
use Tickets\Shared\Domain\Bus\Query\Response;
use Tickets\Shared\Domain\Entity\AbstractionEntity;
use Tickets\Shared\Domain\ValueObject\Status;
use Tickets\Shared\Domain\ValueObject\Uuid;

class OrderTicketItem extends AbstractionEntity implements Response
{
    public function __construct(
        protected Uuid $id,
        protected string $name,
        protected float $price,
        protected int $count,
        protected Status $status,
        protected Carbon $dateBuy,
        protected Carbon $dateCreate,
        protected ?string $lastComment = null,
        protected ?string $linkToTicket = null,
    ) {
    }

    /**
     * @throws JsonException
     */
    public static function fromState(array $data): self
    {
        $price = $data['price'] - $data['discount'];
        return new self(
            new Uuid($data['id']),
            $data['name'],
            (float) $price,
            count(json_decode($data['guests'], false, 512, JSON_THROW_ON_ERROR)),
            new Status($data['status']),
            new Carbon($data['date']),
            new Carbon($data['created_at']),
            $data['last_comment'] ?? null,
            $data['linkToTicket'] ?? null,
        );
    }

}
