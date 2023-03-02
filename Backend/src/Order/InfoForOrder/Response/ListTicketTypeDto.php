<?php

namespace Tickets\Order\InfoForOrder\Response;

use Tickets\Shared\Domain\Bus\Query\Response;
use Tickets\Shared\Domain\Entity\AbstractionEntity;

class ListTicketTypeDto extends AbstractionEntity implements Response
{
    public function __construct(
        protected array $ticketType,
    ){
    }

    public function getTicketType(): array
    {
        return $this->ticketType;
    }
}
