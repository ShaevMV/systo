<?php

declare(strict_types=1);

namespace Baza\Festival\Applications\SetActiveForKpp;

use Baza\Shared\Domain\Bus\Command\Command;

class SetActiveForKppCommand implements Command
{
    public function __construct(
        private string $festivalId,
        private bool $active,
    ) {
    }

    public function getFestivalId(): string
    {
        return $this->festivalId;
    }

    public function isActive(): bool
    {
        return $this->active;
    }
}
