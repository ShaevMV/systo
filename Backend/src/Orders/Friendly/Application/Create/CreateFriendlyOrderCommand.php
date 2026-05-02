<?php

declare(strict_types=1);

namespace Tickets\Orders\Friendly\Application\Create;

use Shared\Domain\Bus\Command\Command;
use Shared\Domain\ValueObject\Uuid;
use Tickets\Orders\Friendly\Dto\FriendlyOrderDto;

final class CreateFriendlyOrderCommand implements Command
{
    public function __construct(
        public readonly FriendlyOrderDto $dto,
        public readonly ?Uuid            $actorId = null,
    ) {}
}
