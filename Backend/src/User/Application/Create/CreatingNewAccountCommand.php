<?php

declare(strict_types=1);

namespace Tickets\User\Application\Create;

use Tickets\Shared\Domain\Bus\Command\Command;

final class CreatingNewAccountCommand implements Command
{
    public function __construct(
        private string $email,
        private string $password,
    ) {
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPassword(): string
    {
        return $this->password;
    }
}
