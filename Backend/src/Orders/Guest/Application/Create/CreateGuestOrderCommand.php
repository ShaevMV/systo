<?php

declare(strict_types=1);

namespace Tickets\Orders\Guest\Application\Create;

use Shared\Domain\Bus\Command\Command;
use Tickets\Orders\Guest\Dto\GuestOrderDto;

final class CreateGuestOrderCommand implements Command
{
    public function __construct(
        public readonly GuestOrderDto $dto,
    ) {}
}
