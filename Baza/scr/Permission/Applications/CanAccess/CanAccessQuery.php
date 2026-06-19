<?php

declare(strict_types=1);

namespace Baza\Permission\Applications\CanAccess;

use Baza\Shared\Domain\Bus\Query\Query;

class CanAccessQuery implements Query
{
    public function __construct(
        private string $role,
        private string $action,
    )
    {
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function getAction(): string
    {
        return $this->action;
    }
}
