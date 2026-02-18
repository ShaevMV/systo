<?php

declare(strict_types=1);

namespace Tickets\User\Account\Application\Edit;

use Shared\Domain\Bus\Command\CommandHandler;
use Tickets\User\Account\Repositories\UserRepositoriesInterface;

class AccountEditCommandHandler implements CommandHandler
{
    public function __construct(
        private UserRepositoriesInterface $repositories
    )
    {
    }

    public function __invoke(AccountEditCommand $accountEditCommand)
    {
        if(!$this->repositories->edit($accountEditCommand->getId(), $accountEditCommand->getUserInfoDto())){
            throw new \DomainException('User ' . $accountEditCommand->getId()->value() . ' not update ');
        }
    }
}
