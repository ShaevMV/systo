<?php

declare(strict_types=1);

namespace Tickets\TicketType\Application\Create;

use Shared\Domain\Bus\Command\Command;
use Shared\Domain\ValueObject\Uuid;
use Tickets\TicketType\Dto\TicketTypeDto;
use Tickets\TypesOfPayment\Dto\TypesOfPaymentDto;

class TicketTypeCreateCommand implements Command
{
    public function __construct(
        private TicketTypeDto $data,
    )
    {
    }

    public function getData(): TicketTypeDto
    {
        return $this->data;
    }
}
