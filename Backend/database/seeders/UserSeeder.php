<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Tickets\User\Account\Helpers\AccountRoleHelper;

/**
 * Сидер тестовых пользователей.
 *
 * Создаёт по одному пользователю на каждую роль из {@see AccountRoleHelper}.
 *
 * Идемпотентен через `User::updateOrCreate(['id' => ...])` — повторный запуск
 * обновляет существующих юзеров, не дублирует. UUID-ы фиксированы как константы
 * класса.
 *
 * **Логины/пароли — в `.claude/docs/SEEDED_USERS.md`.**
 *
 * Для совместимости с legacy-кодом каждому юзеру проставляется:
 * - `role` (новое поле, используется в `CheckRole` middleware)
 * - `is_admin` (legacy флаг, проверяется в `IsAdmin` middleware ИЛИ `role='admin'`)
 * - `is_manager` (legacy флаг, для manager-юзера)
 */
class UserSeeder extends Seeder
{
    /**
     * Единый пароль для всех тестовых юзеров. Хранится в `SEEDED_USERS.md`.
     * Только для test/staging — на проде сиды не запускаются.
     */
    public const PASSWORD = 'password';

    // Фиксированные UUID — для идемпотентности и для ссылок из других сидеров (OrderSeeder)
    public const ID_FOR_ADMIN_UUID = 'b9df62af-252a-4890-afd7-73c2a356c259';
    public const ID_FOR_USER_UUID = 'b9df62af-252a-4890-afd7-73c2a356c260';
    public const ID_FOR_MANAGER_UUID = 'b9df62af-252a-4890-afd7-73c2a356c261';
    public const ID_FOR_SELLER_UUID = 'b9df62af-252a-4890-afd7-73c2a356c262';
    public const ID_FOR_PUSHER_UUID = 'b9df62af-252a-4890-afd7-73c2a356c263';
    public const ID_FOR_CURATOR_UUID = 'b9df62af-252a-4890-afd7-73c2a356c264';
    public const ID_FOR_PUSHER_CURATOR_UUID = 'b9df62af-252a-4890-afd7-73c2a356c265';

    public const EMAIL_ADMIN = 'admin@spaceofjoy.ru';
    public const EMAIL_USER = 'shaevmv@gmail.com';
    public const EMAIL_MANAGER = 'lesystoe@spaceofjoy.ru';
    public const EMAIL_SELLER = 'seller@staging.local';
    public const EMAIL_PUSHER = 'pusher@staging.local';
    public const EMAIL_CURATOR = 'curator@staging.local';
    public const EMAIL_PUSHER_CURATOR = 'pushcurator@staging.local';

    public function run(): void
    {
        $this->seedUser([
            'id' => self::ID_FOR_ADMIN_UUID,
            'name' => 'Admin',
            'email' => self::EMAIL_ADMIN,
            'role' => AccountRoleHelper::admin,
            'is_admin' => true,
            'is_manager' => false,
        ]);

        $this->seedUser([
            'id' => self::ID_FOR_USER_UUID,
            'name' => 'Guest User',
            'email' => self::EMAIL_USER,
            'role' => AccountRoleHelper::guest,
            'is_admin' => false,
            'is_manager' => false,
        ]);

        $this->seedUser([
            'id' => self::ID_FOR_MANAGER_UUID,
            'name' => 'Manager',
            'email' => self::EMAIL_MANAGER,
            'role' => AccountRoleHelper::manager,
            'is_admin' => false,
            'is_manager' => true,
        ]);

        $this->seedUser([
            'id' => self::ID_FOR_SELLER_UUID,
            'name' => 'Seller',
            'email' => self::EMAIL_SELLER,
            'role' => AccountRoleHelper::seller,
            'is_admin' => false,
            'is_manager' => false,
        ]);

        $this->seedUser([
            'id' => self::ID_FOR_PUSHER_UUID,
            'name' => 'Pusher (Friendly)',
            'email' => self::EMAIL_PUSHER,
            'role' => AccountRoleHelper::pusher,
            'is_admin' => false,
            'is_manager' => false,
        ]);

        $this->seedUser([
            'id' => self::ID_FOR_CURATOR_UUID,
            'name' => 'Curator',
            'email' => self::EMAIL_CURATOR,
            'role' => AccountRoleHelper::curator,
            'is_admin' => false,
            'is_manager' => false,
        ]);

        $this->seedUser([
            'id' => self::ID_FOR_PUSHER_CURATOR_UUID,
            'name' => 'Pusher + Curator',
            'email' => self::EMAIL_PUSHER_CURATOR,
            'role' => AccountRoleHelper::pusher_curator,
            'is_admin' => false,
            'is_manager' => false,
        ]);
    }

    /**
     * Идемпотентное создание/обновление юзера через Eloquent.
     *
     * `is_admin`/`is_manager` НЕ в `$fillable` модели User — установка через
     * `forceFill()` (обходит mass assignment guard). Это безопасно: сидер запускается
     * только разработчиком на test/staging.
     */
    private function seedUser(array $cfg): void
    {
        $user = User::updateOrCreate(
            ['id' => $cfg['id']],
            [
                'name' => $cfg['name'],
                'email' => $cfg['email'],
                'phone' => '+79999999999',
                'city' => 'spb',
                'role' => $cfg['role'],
                'password' => Hash::make(self::PASSWORD),
            ]
        );

        $user->forceFill([
            'is_admin' => $cfg['is_admin'],
            'is_manager' => $cfg['is_manager'],
        ])->save();
    }
}
