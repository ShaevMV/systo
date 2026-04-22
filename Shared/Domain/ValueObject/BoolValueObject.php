<?php

declare(strict_types=1);

namespace Shared\Domain\ValueObject;

abstract class BoolValueObject
{
    public function __construct(protected ?bool $value)
    {
    }

    public function value(): ?bool
    {
        return $this->value;
    }
}
