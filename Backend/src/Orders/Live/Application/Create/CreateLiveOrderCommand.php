<?php

declare(strict_types=1);

namespace Tickets\Orders\Live\Application\Create;

use Shared\Domain\Bus\Command\Command;
use Tickets\Orders\Live\Dto\LiveOrderDto;

final class CreateLiveOrderCommand implements Command
{
    public function __construct(
        public readonly LiveOrderDto $dto,
    ) {}
}
