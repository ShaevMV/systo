<?php

declare(strict_types=1);

namespace Baza\Permission\Applications\CanAccess;

use Baza\Shared\Domain\Bus\Query\Response;

class CanAccessResponse implements Response
{
    public function __construct(
        private bool $allowed,
    )
    {
    }

    public function isAllowed(): bool
    {
        return $this->allowed;
    }
}
