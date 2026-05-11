<?php

declare(strict_types=1);

namespace Tickets\TicketTypePrice\Application\Edit;

use Shared\Domain\Bus\Command\Command;
use Shared\Domain\ValueObject\Uuid;
use Tickets\TicketTypePrice\Dto\TicketTypePriceDto;

class TicketTypePriceEditCommand implements Command
{
    public function __construct(
        private Uuid $id,
        private TicketTypePriceDto $data,
    ) {
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getData(): TicketTypePriceDto
    {
        return $this->data;
    }
}
