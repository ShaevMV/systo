<?php

declare(strict_types=1);

namespace Shared\Domain;

interface UuidGenerator
{
    public function generate(): string;
}
