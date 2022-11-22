<?php

declare(strict_types=1);

namespace Tickets\Shared\Domain;

interface UuidGenerator
{
    public function generate(): string;
}
