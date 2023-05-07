<?php

declare(strict_types=1);

namespace Baza\Changes\Applications\OpenAndClose\Open;

use Baza\Shared\Domain\Bus\Command\Command;

class OpenChangeCommand implements Command
{
    public function __construct(
        private int $userId,
    )
    {
    }

    public function getUserId(): int
    {
        return $this->userId;
    }
}
