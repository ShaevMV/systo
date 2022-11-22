<?php

declare(strict_types=1);

namespace Tickets\Shared\Domain\Bus\Command;

interface CommandBus
{
    public function dispatch(Command $command): void;
}
