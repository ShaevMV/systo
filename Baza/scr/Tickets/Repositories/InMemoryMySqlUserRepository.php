<?php

declare(strict_types=1);

namespace Baza\Tickets\Repositories;

use App\Models\User;

class InMemoryMySqlUserRepository implements UserRepositoryInterface
{
    public function __construct(
        private User $model)
    {
    }


    public function createList(array $dataUsers): bool
    {
        $userInsert = [];
        foreach ($dataUsers as $user) {
            $user['password'] = bcrypt($user['password']);
            $userInsert[] = $user;
        }
        return $this->model::insert($userInsert);
    }

    public function clear(): bool
    {
        \DB::table($this->model::TABLE)->truncate();
    }

    public function initAdmin(string $email): bool
    {
        if ($user = $this->model::whereEmail($email)->first()) {
            $user->is_admin = true;
            $user->save();
        }
    }
}
