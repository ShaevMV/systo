<?php

declare(strict_types=1);

namespace Tickets\User\Account\Application\Find\ByEmail;

use Tickets\Shared\Domain\Bus\Query\QueryHandler;
use Tickets\User\Account\Dto\UserInfoDto;
use Tickets\User\Account\Repositories\AccountInterface;

final class AccountFindByEmailQueryHandler implements QueryHandler
{
    public function __construct(
        private AccountInterface $account
    ) {
    }

    public function __invoke(AccountFindByEmailQuery $query): ?UserInfoDto
    {
        return $this->account->findAccountByEmail($query->getEmail());
    }
}
