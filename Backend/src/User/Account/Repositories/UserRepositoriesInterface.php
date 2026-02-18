<?php

declare(strict_types = 1);

namespace Tickets\User\Account\Repositories;

use Shared\Domain\Criteria\Filters;
use Shared\Domain\ValueObject\Uuid;
use Tickets\User\Account\Dto\AccountDto;
use Tickets\User\Account\Dto\UserInfoDto;

interface UserRepositoriesInterface
{
    public function create(AccountDto $accountDto, string $password): bool;
    public function findAccountByEmail(string $email): ?UserInfoDto;
    public function findAccountById(Uuid $id): ?UserInfoDto;

    /**
     * @return UserInfoDto[]
     */
    public function getList(Filters $filters): array;

    public function edit(Uuid $id, UserInfoDto $userInfoDto): bool;
}
