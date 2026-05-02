<?php

declare(strict_types=1);

namespace Tickets\Orders\Guest\Application\ChangeStatus;

use Shared\Domain\Bus\Command\Command;
use Shared\Domain\ValueObject\Status;
use Shared\Domain\ValueObject\Uuid;
use Tickets\History\Domain\ActorType;

final class ChangeGuestOrderStatusCommand implements Command
{
    public function __construct(
        public readonly Uuid    $orderId,
        public readonly Status  $newStatus,
        public readonly array   $params    = [],
        public readonly ?Uuid   $actorId   = null,
        public readonly string  $actorType = ActorType::USER,
    ) {}
}
