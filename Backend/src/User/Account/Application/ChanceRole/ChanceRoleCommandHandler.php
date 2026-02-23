<?php

namespace Tickets\User\Account\Application\ChanceRole;

use Tickets\User\Account\Repositories\UserRepositoriesInterface;

class ChanceRoleCommandHandler implements \Shared\Domain\Bus\Command\CommandHandler
{
    public function __construct(
        private UserRepositoriesInterface $repositories
    )
    {
    }

    public function __invoke(ChanceRoleCommand $command): void
    {
        $this->repositories->chanceRole($command->getId(), $command->getRole());
    }
}
