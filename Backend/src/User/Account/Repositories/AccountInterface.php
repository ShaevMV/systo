<?php

declare(strict_types = 1);

namespace Tickets\User\Account\Repositories;

use Tickets\Shared\Domain\ValueObject\Uuid;
use Tickets\User\Account\Dto\AccountDto;
use Tickets\User\Account\Dto\UserInfoDto;

interface AccountInterface
{
    public function create(AccountDto $accountDto, string $password): bool;
    public function findAccountByEmail(string $email): ?UserInfoDto;

    public function findAccountById(Uuid $id): ?UserInfoDto;
}
