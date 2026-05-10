<?php

declare(strict_types=1);

namespace Tickets\TicketTypePrice\Application\Create;

use Shared\Domain\Bus\Command\Command;
use Tickets\TicketTypePrice\Dto\TicketTypePriceDto;

class TicketTypePriceCreateCommand implements Command
{
    public function __construct(
        private TicketTypePriceDto $data,
    ) {
    }

    public function getData(): TicketTypePriceDto
    {
        return $this->data;
    }
}
