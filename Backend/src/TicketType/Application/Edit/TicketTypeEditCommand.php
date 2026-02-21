<?php

declare(strict_types=1);

namespace Tickets\TicketType\Application\Edit;

use Shared\Domain\Bus\Command\Command;
use Shared\Domain\ValueObject\Uuid;
use Tickets\TicketType\Dto\TicketTypeDto;
class TicketTypeEditCommand implements Command
{
    public function __construct(
        private Uuid $id,
        private TicketTypeDto $paymentDto,
    )
    {
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getPaymentDto(): TicketTypeDto
    {
        return $this->paymentDto;
    }
}
