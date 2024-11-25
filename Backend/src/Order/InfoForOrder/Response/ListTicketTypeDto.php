<?php

namespace Tickets\Order\InfoForOrder\Response;

use Shared\Domain\Bus\Query\Response;
use Shared\Domain\Entity\AbstractionEntity;

class ListTicketTypeDto extends AbstractionEntity implements Response
{
    /**
     * @param TicketTypeDto[] $ticketType
     */
    public function __construct(
        protected array $ticketType,
    ){
    }

    public function getTicketType(): array
    {
        return $this->ticketType;
    }
}
