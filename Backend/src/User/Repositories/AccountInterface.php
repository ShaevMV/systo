<?php

declare(strict_types = 1);

namespace Tickets\User\Repositories;

use Tickets\User\Dto\AccountDto;

interface AccountInterface
{
    public function create(AccountDto $accountDto): bool;
    public function findAccountByEmail(string $email): ?AccountDto;
}
