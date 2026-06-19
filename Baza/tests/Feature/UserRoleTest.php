<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ChangesModel;
use App\Models\ChangeUserModel;
use App\Models\User;
use Baza\Changes\Applications\SaveChange\SaveChange;
use Baza\Shared\Domain\ValueObject\ShiftRole;
use Baza\Tickets\Repositories\UserRepositoryInterface;
use Carbon\Carbon;
use Database\Seeders\UsersTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Тесты глобальной роли пользователя users.role + мягкого маппинга (Baza, Ф2 PR-3).
 *
 * users.role (nullable) — явная роль смены, перекрывает производную по is_admin
 * (ShiftRole::fromUser). Заводится сидером/командой (опц. 5-я колонка списка),
 * читается при пересборке состава смены в change_user. БД baza_test.
 */
class UserRoleTest extends TestCase
{
    use RefreshDatabase;

    private function repo(): UserRepositoryInterface
    {
        return app(UserRepositoryInterface::class);
    }

    public function test_create_list_sets_explicit_valid_role(): void
    {
        $this->repo()->createList([
            ['email' => 'guard@test.local', 'name' => 'Охрана', 'password' => 'p', 'is_admin' => false, 'role' => ShiftRole::GUARD],
        ]);

        self::assertSame(ShiftRole::GUARD, User::where('email', 'guard@test.local')->first()->role);
    }

    public function test_create_list_ignores_invalid_role(): void
    {
        $this->repo()->createList([
            ['email' => 'bad@test.local', 'name' => 'Кривой', 'password' => 'p', 'is_admin' => false, 'role' => 'superuser'],
        ]);

        self::assertNull(User::where('email', 'bad@test.local')->first()->role, 'невалидная роль не записывается');
    }

    public function test_create_list_without_role_leaves_null(): void
    {
        $this->repo()->createList([
            ['email' => 'norole@test.local', 'name' => 'Без роли', 'password' => 'p', 'is_admin' => true],
        ]);

        // role не задан → null; роль выведется по is_admin при использовании
        self::assertNull(User::where('email', 'norole@test.local')->first()->role);
    }

    public function test_shift_uses_explicit_user_role_over_is_admin_mapping(): void
    {
        $this->seed(UsersTableSeeder::class); // user 1: is_admin=true; user 3: is_admin=false

        // user 3 — обычный (is_admin=false), но с явной глобальной ролью guard
        $u3 = User::find(3);
        $u3->role = ShiftRole::GUARD;
        $u3->save();

        app(SaveChange::class)->save([1, 3], Carbon::now());
        $change = ChangesModel::query()->latest('id')->first();

        $rows = ChangeUserModel::where('change_id', $change->id)->get()->keyBy('user_id');
        // явная role перекрывает is_admin-маппинг
        self::assertSame(ShiftRole::GUARD, $rows[3]->role, 'явная users.role используется в составе смены');
        // user 1 без role → производная administrator (is_admin=true)
        self::assertSame(ShiftRole::ADMINISTRATOR, $rows[1]->role);
    }
}
