<?php

namespace Tickets\Festival\Response;

use Shared\Domain\Bus\Query\Response;
use Shared\Domain\Entity\AbstractionEntity;

final class ListTicketTypeDto extends AbstractionEntity implements Response
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
