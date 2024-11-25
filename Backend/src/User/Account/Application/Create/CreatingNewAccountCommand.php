<?php

declare(strict_types=1);

namespace Tickets\User\Account\Application\Create;

use Shared\Domain\Bus\Command\Command;
use Tickets\User\Account\Dto\AccountDto;

final class CreatingNewAccountCommand implements Command
{
    public function __construct(
        private AccountDto $accountDto,
        private string $password,
    ) {
    }

    public function getAccountDto(): AccountDto
    {
        return $this->accountDto;
    }

    public function getPassword(): string
    {
        return $this->password;
    }
}
