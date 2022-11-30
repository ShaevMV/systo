<?php

declare(strict_types=1);

namespace Tickets\User\Account\Application\Find;

use Tickets\User\Account\Repositories\AccountInterface;
use Tickets\User\Account\Response\IdAccountResponse;

final class AccountFindQueryHandler
{
    public function __construct(
        private AccountInterface $account
    ) {
    }

    public function __invoke(AccountFindQuery $query): ?IdAccountResponse
    {
        $accountDto = $this->account->findAccountByEmail($query->getEmail());

        return !is_null($accountDto) ? new IdAccountResponse($accountDto->getId()) : null;
    }
}
