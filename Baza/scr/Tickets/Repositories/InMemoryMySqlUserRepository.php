<?php

declare(strict_types=1);

namespace Baza\Tickets\Repositories;

use App\Models\User;
use Baza\Shared\Domain\ValueObject\ShiftRole;

class InMemoryMySqlUserRepository implements UserRepositoryInterface
{
    public function __construct(
        private User $model)
    {
    }


    /**
     * Идемпотентное заведение/обновление персонала по email.
     *
     * Раньше был insert() — падал на повторе (дубль email) и не давал
     * перевыпускать пароли. Теперь updateOrCreate по email: повторный прогон
     * обновляет имя/пароль существующего сотрудника, не создавая дублей.
     *
     * @param array<int, array{email:string, name:string, password:string, is_admin?:bool, role?:string}> $dataUsers
     */
    public function createList(array $dataUsers): bool
    {
        foreach ($dataUsers as $user) {
            /** @var User $model */
            $model = $this->model::updateOrCreate(
                ['email' => $user['email']],
                [
                    'name'     => $user['name'],
                    'password' => bcrypt($user['password']),
                ]
            );
            // is_admin и role сознательно вне $fillable (защита от mass-assignment) —
            // выставляем напрямую, не ослабляя модель.
            $model->is_admin = (bool) ($user['is_admin'] ?? false);

            // Явная глобальная роль смены (опционально). Невалидную игнорируем —
            // тогда роль выведется по is_admin (ShiftRole::fromUser).
            if (isset($user['role']) && ShiftRole::isValid((string) $user['role'])) {
                $model->role = (string) $user['role'];
            }

            $model->save();
        }

        return true;
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

    public function list(): array
    {
        return $this->model::query()
            ->orderBy('id')
            ->get(['id', 'name', 'email', 'is_admin', 'role'])
            ->map(function (User $u): array {
                $role = ShiftRole::fromUser((bool) $u->is_admin, $u->role);

                return [
                    'id' => (int) $u->id,
                    'name' => (string) $u->name,
                    'email' => (string) $u->email,
                    'is_admin' => (bool) $u->is_admin,
                    'role' => $role,
                    'role_label' => ShiftRole::label($role),
                ];
            })
            ->all();
    }
}
