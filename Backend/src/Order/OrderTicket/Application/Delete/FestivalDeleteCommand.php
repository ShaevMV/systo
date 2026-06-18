<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Application\Delete;

use Shared\Domain\Bus\Command\Command;
use Shared\Domain\ValueObject\Uuid;

class FestivalDeleteCommand implements Command
{
    public function __construct(
        private Uuid $id
    ) {
    }

    public function getId(): Uuid
    {
        return $this->id;
    }
}
