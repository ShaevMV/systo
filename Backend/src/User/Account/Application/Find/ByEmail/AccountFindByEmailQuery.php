<?php

declare(strict_types=1);

namespace Tickets\User\Account\Application\Find\ByEmail;

use Shared\Domain\Bus\Query\Query;

final class AccountFindByEmailQuery implements Query
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
