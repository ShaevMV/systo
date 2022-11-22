<?php

declare(strict_types=1);

namespace Tickets\User\Application\Find;

use Tickets\Shared\Domain\Bus\Query\Query;

final class AccountFindQuery implements Query
{
    public function __construct(
        private string $email
    ) {
    }

    public function getEmail(): string
    {
        return $this->email;
    }
}
