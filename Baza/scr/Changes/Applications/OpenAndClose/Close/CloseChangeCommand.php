<?php

declare(strict_types=1);

namespace Baza\Changes\Applications\OpenAndClose\Close;

use Baza\Shared\Domain\Bus\Command\Command;

class CloseChangeCommand implements Command
{
    public function __construct(
        private int $changeId,
    )
    {
    }

    public function getChangeId(): int
    {
        return $this->changeId;
    }
}
