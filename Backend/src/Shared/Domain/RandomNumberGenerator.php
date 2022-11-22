<?php

declare(strict_types=1);

namespace Tickets\Shared\Domain;

interface RandomNumberGenerator
{
    public function generate(): int;
}
