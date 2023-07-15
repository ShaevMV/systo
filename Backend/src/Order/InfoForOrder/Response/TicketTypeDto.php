<?php

declare(strict_types=1);

namespace Tickets\Order\InfoForOrder\Response;

use Tickets\Order\OrderTicket\Helpers\FestivalHelper;
use Shared\Domain\Bus\Query\Response;
use Shared\Domain\Entity\AbstractionEntity;
use Shared\Domain\ValueObject\Uuid;

final class TicketTypeDto extends AbstractionEntity implements Response
{
    protected Uuid $festivalId;

    public function __construct(
        protected Uuid   $id,
        protected string $name,
        protected float  $price,
        protected ?int   $groupLimit = null,
        ?Uuid            $festivalId = null,
        protected int    $sort = 0,
    )
    {
        $this->festivalId = $festivalId ?? new Uuid(FestivalHelper::UUID_FESTIVAL);
    }

    public static function fromState(array $data): self
    {
        $groupLimit = isset($data['groupLimit']) && !empty($data['groupLimit']) ?
            (int)$data['groupLimit'] :
            null;

        $price = !isset($data['ticket_type_price']) || count($data['ticket_type_price']) == 0 ? $data['price'] : end($data['ticket_type_price'])['price'];
        $festivalId = !isset($data['festival_id']) ? null : new Uuid($data['festival_id']);

        return new self(
            new Uuid($data['id']),
            $data['name'],
            (float)$price,
            $groupLimit,
            $festivalId,
            $data['sort'],
        );
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function getGroupLimit(): ?int
    {
        return $this->groupLimit;
    }
}
