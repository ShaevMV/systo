<?php

declare(strict_types = 1);

namespace Tickets\User\Application\Create;

use Tickets\User\Dto\AccountDto;
use Tickets\User\Repositories\AccountInterface;

final class CreatingNewAccountCommandHandler
{
    public function __construct(
        private AccountInterface $account
    ){
    }

    public function __invoke(CreatingNewAccountCommand $command): void
    {
        $this->account->create(new AccountDto(
            $command->getEmail(),
            $command->getPassword()
        ));
    }
}
