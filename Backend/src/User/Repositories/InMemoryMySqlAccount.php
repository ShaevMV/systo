<?php

declare(strict_types = 1);

namespace Tickets\User\Repositories;

use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Throwable;
use Tickets\User\Dto\AccountDto;

final class InMemoryMySqlAccount implements AccountInterface
{
    public function __construct(
        private User $model
    ){
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

    public function findAccountByEmail(string $email): ?AccountDto
    {
        if($findAccount = $this->model::whereEmail($email)->first()?->toArray()) {
            return AccountDto::fromState(
                $findAccount
            );
        }

        return null;
    }
}
