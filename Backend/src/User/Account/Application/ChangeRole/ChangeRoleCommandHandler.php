<?php

namespace Tickets\User\Account\Application\ChangeRole;

use Tickets\User\Account\Repositories\UserRepositoriesInterface;

class ChangeRoleCommandHandler implements \Shared\Domain\Bus\Command\CommandHandler
{
    public function __construct(
        private UserRepositoriesInterface $repositories
    )
    {
    }

    public function __invoke(ChangeRoleCommand $command): void
    {
        $this->repositories->changeRole($command->getId(), $command->getRole());
    }
}
