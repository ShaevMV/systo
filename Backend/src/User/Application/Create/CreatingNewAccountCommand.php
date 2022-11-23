<?php

declare(strict_types=1);

namespace Tickets\User\Application\Create;

use Tickets\Shared\Domain\Bus\Command\Command;
use Tickets\User\Dto\AccountDto;

final class CreatingNewAccountCommand implements Command
{
    public function __construct(
        private AccountDto $accountDto,
    ) {
    }

    public function getAccountDto(): AccountDto
    {
        return $this->accountDto;
    }
}
