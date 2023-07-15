<?php

declare(strict_types=1);

namespace Tickets\User\Account\Repositories;

use App\Models\User;
use Carbon\Carbon;
use Exception;
use Hash;
use Illuminate\Support\Facades\DB;
use Throwable;
use Shared\Domain\ValueObject\Uuid;
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
    public function create(
        AccountDto $accountDto,
        string $password
    ): bool
    {
        DB::beginTransaction();
        try {
            $this->model::insert(
                array_merge(
                    $accountDto->toArray(),
                    ['password' => Hash::make($password)],
                    [
                        'created_at' => (string) (new Carbon()),
                        'updated_at' => (string) (new Carbon()),
                    ]
                )
            );
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
