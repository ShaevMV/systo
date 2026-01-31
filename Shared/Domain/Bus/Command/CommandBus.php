<?php

declare(strict_types=1);

namespace Shared\Domain\Bus\Command;

use Shared\Infrastructure\Bus\Command\CommandResponse;

interface CommandBus
{
    public function dispatch(Command $command): ?CommandResponse;
}
