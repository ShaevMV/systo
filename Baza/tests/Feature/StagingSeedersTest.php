<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\BazaRolePermissionModel;
use App\Models\ChangesModel;
use App\Models\ChangeUserModel;
use App\Models\User;
use Baza\Shared\Domain\ValueObject\ShiftRole;
use Database\Seeders\StagingDemoSeeder;
use Database\Seeders\UsersTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Тесты понятных идемпотентных сидеров стенда (Baza, TD-41).
 *
 * UsersTableSeeder — идемпотентен (force-seed на стенде не падает duplicate key).
 * StagingDemoSeeder — раздаёт разные роли смены + матрицу + открывает демо-смену,
 * идемпотентно. БД baza_test.
 */
class StagingSeedersTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_seeder_is_idempotent(): void
    {
        $this->seed(UsersTableSeeder::class);
        $this->seed(UsersTableSeeder::class); // повтор не должен падать duplicate key

        self::assertSame(36, User::count());
    }

    public function test_demo_seeder_assigns_distinct_shift_roles(): void
    {
        $this->seed(UsersTableSeeder::class);
        $this->seed(StagingDemoSeeder::class);

        self::assertSame(ShiftRole::SHIFT_CHIEF, User::where('email', 'YulyaRahlina@spaceofjoy.ru')->first()->role);
        self::assertSame(ShiftRole::TICKETER, User::where('email', 'KostyaIhti@spaceofjoy.ru')->first()->role);
        self::assertSame(ShiftRole::KPP_COMMANDANT, User::where('email', 'Infocentr1@spaceofjoy.ru')->first()->role);
        self::assertSame(ShiftRole::GUARD, User::where('email', 'Ohrana1@spaceofjoy.ru')->first()->role);
    }

    public function test_demo_seeder_seeds_default_matrix(): void
    {
        $this->seed(UsersTableSeeder::class);
        $this->seed(StagingDemoSeeder::class);

        self::assertGreaterThan(0, BazaRolePermissionModel::count());
        // у ticketer есть впуск, но нет sync
        self::assertTrue(BazaRolePermissionModel::where('role', ShiftRole::TICKETER)->where('action', 'ticket.enter')->exists());
        self::assertFalse(BazaRolePermissionModel::where('role', ShiftRole::TICKETER)->where('action', 'sync.manage')->exists());
    }

    public function test_demo_seeder_is_idempotent_one_open_shift_with_roles(): void
    {
        $this->seed(UsersTableSeeder::class);
        $this->seed(StagingDemoSeeder::class);
        $this->seed(StagingDemoSeeder::class); // повтор не открывает вторую смену

        self::assertSame(36, User::count());
        self::assertSame(1, ChangesModel::query()->whereNull('end')->count(), 'ровно одна открытая демо-смена');

        // роль начальника смены попала в состав change_user (из users.role)
        $change = ChangesModel::query()->latest('id')->first();
        $chief = User::where('email', 'YulyaRahlina@spaceofjoy.ru')->first();
        self::assertSame(
            ShiftRole::SHIFT_CHIEF,
            ChangeUserModel::where('change_id', $change->id)->where('user_id', $chief->id)->first()->role
        );
    }
}
