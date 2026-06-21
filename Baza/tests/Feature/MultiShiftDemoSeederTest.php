<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\BazaRolePermissionModel;
use App\Models\ChangesModel;
use App\Models\ChangeUserModel;
use App\Models\User;
use Baza\Shared\Domain\ValueObject\ShiftRole;
use Database\Seeders\MultiShiftDemoSeeder;
use Database\Seeders\UsersTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Демо-сидер RBAC: две открытые смены с РАЗНЫМИ начальниками + 5 ролей (Baza, TD-41 / Ф2).
 *
 * Цель — чтобы владелец увидел RBAC и изоляцию смен вживую. Тест доказывает:
 * идемпотентность (двойной прогон без дублей), две открытые смены с разными
 * начальниками, роль shift_chief каждому начальнику в change_user, наличие матрицы прав,
 * чистая демо-почта (без «test»). БД baza_test.
 */
class MultiShiftDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(UsersTableSeeder::class);
    }

    public function test_assigns_five_distinct_shift_roles(): void
    {
        $this->seed(MultiShiftDemoSeeder::class);

        self::assertSame(ShiftRole::ADMINISTRATOR, User::where('email', 'admin@admin.ru')->first()->role);
        self::assertSame(ShiftRole::SHIFT_CHIEF, User::where('email', 'YulyaRahlina@spaceofjoy.ru')->first()->role);
        self::assertSame(ShiftRole::SHIFT_CHIEF, User::where('email', 'Lera@spaceofjoy.ru')->first()->role);
        self::assertSame(ShiftRole::TICKETER, User::where('email', 'KostyaIhti@spaceofjoy.ru')->first()->role);
        self::assertSame(ShiftRole::KPP_COMMANDANT, User::where('email', 'Infocentr1@spaceofjoy.ru')->first()->role);
        self::assertSame(ShiftRole::GUARD, User::where('email', 'Ohrana1@spaceofjoy.ru')->first()->role);
    }

    public function test_opens_two_shifts_with_distinct_chiefs(): void
    {
        $this->seed(MultiShiftDemoSeeder::class);

        self::assertSame(2, ChangesModel::query()->whereNull('end')->count(), 'ровно две открытые смены');

        $chiefRows = ChangeUserModel::where('role', ShiftRole::SHIFT_CHIEF)->get();
        $chiefUserIds = $chiefRows->pluck('user_id')->unique()->values()->all();
        $chiefChangeIds = $chiefRows->pluck('change_id')->unique()->values()->all();

        self::assertEqualsCanonicalizing([3, 8], $chiefUserIds, 'начальники разных смен — id 3 и 8');
        self::assertCount(2, $chiefChangeIds, 'каждый начальник в своей смене (изоляция)');
    }

    public function test_each_chief_has_shift_chief_role_in_change_user(): void
    {
        $this->seed(MultiShiftDemoSeeder::class);

        foreach ([3, 8] as $chiefId) {
            $row = ChangeUserModel::where('user_id', $chiefId)
                ->where('role', ShiftRole::SHIFT_CHIEF)
                ->first();

            self::assertNotNull($row, "у начальника id={$chiefId} роль shift_chief в составе смены");

            // он действительно ведёт ОТКРЫТУЮ смену
            $change = ChangesModel::find($row->change_id);
            self::assertNotNull($change);
            self::assertNull($change->end, "смена начальника id={$chiefId} открыта");
        }
    }

    public function test_members_get_derived_roles_in_change_user(): void
    {
        $this->seed(MultiShiftDemoSeeder::class);

        // Состав смены наследует роли из users.role (мягкий маппинг syncChangeUsers).
        $roleByUser = ChangeUserModel::all()->keyBy('user_id');

        self::assertSame(ShiftRole::TICKETER, $roleByUser[4]->role, 'билетёр смены №1');
        self::assertSame(ShiftRole::KPP_COMMANDANT, $roleByUser[9]->role, 'комендант КПП смены №1');
        self::assertSame(ShiftRole::GUARD, $roleByUser[33]->role, 'охранник смены №1');
        self::assertSame(ShiftRole::TICKETER, $roleByUser[10]->role, 'билетёр смены №2');
        self::assertSame(ShiftRole::GUARD, $roleByUser[34]->role, 'охранник смены №2');
    }

    public function test_seeds_default_permission_matrix(): void
    {
        $this->seed(MultiShiftDemoSeeder::class);

        self::assertGreaterThan(0, BazaRolePermissionModel::count());
        // у билетёра есть впуск, но нет управления синхронизацией
        self::assertTrue(
            BazaRolePermissionModel::where('role', ShiftRole::TICKETER)->where('action', 'ticket.enter')->exists()
        );
        self::assertFalse(
            BazaRolePermissionModel::where('role', ShiftRole::TICKETER)->where('action', 'sync.manage')->exists()
        );
    }

    public function test_is_idempotent_no_duplicate_shifts_or_users(): void
    {
        $this->seed(MultiShiftDemoSeeder::class);
        $this->seed(MultiShiftDemoSeeder::class); // повтор не должен плодить смены/состав

        self::assertSame(36, User::count(), 'персонал не задублирован');
        self::assertSame(2, ChangesModel::query()->whereNull('end')->count(), 'по-прежнему две открытые смены');
        self::assertSame(
            2,
            ChangeUserModel::where('role', ShiftRole::SHIFT_CHIEF)->count(),
            'ровно две строки начальников (без дублей change_user)'
        );
    }

    public function test_demo_emails_have_no_test_token(): void
    {
        $this->seed(MultiShiftDemoSeeder::class);

        // Поиск без QR ищет по email (ticket_search.email) — демо-почта не должна
        // содержать «test», чтобы запрос «test» не давал ложных совпадений.
        $this->seed(\Database\Seeders\TicketSearchTestDataSeeder::class);

        $emails = \App\Models\TicketSearchModel::query()->pluck('email')->filter()->all();
        self::assertNotEmpty($emails);

        foreach ($emails as $email) {
            self::assertStringNotContainsStringIgnoringCase('test', (string) $email, "демо-email без 'test': {$email}");
        }
    }
}
