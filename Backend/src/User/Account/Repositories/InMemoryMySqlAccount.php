<?php

declare(strict_types=1);

namespace Tickets\User\Account\Repositories;

use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Throwable;
use Tickets\Shared\Domain\ValueObject\Uuid;
use Tickets\User\Account\Dto\AccountDto;
use Tickets\User\Account\Dto\UserInfoDto;

final class InMemoryMySqlAccount implements AccountInterface
{
    public function __construct(
        private User $model
    ) {
    }

    /**
     * @throws Throwable
     */
    public function create(AccountDto $accountDto): bool
    {
        DB::beginTransaction();
        try {
            $this->model::create($accountDto->toArray());
            DB::commit();
            return true;
        } catch (Exception $exception) {
            DB::rollBack();
            throw $exception;
        }
    }

    public function findAccountByEmail(string $email): ?UserInfoDto
    {
        if ($findAccount = $this->model::whereEmail($email)->first()?->toArray()) {
            return UserInfoDto::fromState(
                $findAccount
            );
        }

        return null;
    }

    public function findAccountById(Uuid $id): ?UserInfoDto
    {
        if ($findAccount = $this->model::whereId($id->value())->first()?->toArray()) {
            return UserInfoDto::fromState(
                $findAccount
            );
        }

        return null;
    }
}
