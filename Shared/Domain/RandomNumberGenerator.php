<?php

declare(strict_types=1);

namespace Shared\Domain;

interface RandomNumberGenerator
{
    public function generate(): int;
}
