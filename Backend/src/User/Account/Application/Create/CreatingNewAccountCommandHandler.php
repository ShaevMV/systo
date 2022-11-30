<?php

declare(strict_types = 1);

namespace Tickets\User\Account\Application\Create;

use Tickets\User\Account\Repositories\AccountInterface;

final class CreatingNewAccountCommandHandler
{
    public function __construct(
        private AccountInterface $account,
    ){
    }

    public function __invoke(CreatingNewAccountCommand $command): void
    {
        $this->account->create($command->getAccountDto());
    }
}
