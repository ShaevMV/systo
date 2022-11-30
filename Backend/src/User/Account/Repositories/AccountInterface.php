<?php

declare(strict_types = 1);

namespace Tickets\User\Account\Repositories;

use Tickets\User\Account\Dto\AccountDto;

interface AccountInterface
{
    public function create(AccountDto $accountDto): bool;
    public function findAccountByEmail(string $email): ?AccountDto;
}
