<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\BazaRolePermissionModel;
use Baza\Permission\Applications\CanAccess\CanAccess;
use Baza\Permission\Repositories\RolePermissionRepositoryInterface;
use Baza\Shared\Domain\ValueObject\ShiftPermission;
use Baza\Shared\Domain\ValueObject\ShiftRole;
use Database\Seeders\BazaRolePermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Тесты матрицы прав «роль × действие» (Baza, Ф2 PR-4).
 *
 * Дефолтная матрица из BazaRolePermissionsSeeder. administrator — суперроль
 * (короткозамкнута в коде, в таблице нет). БД baza_test.
 */
class RolePermissionTest extends TestCase
{
    use RefreshDatabase;

    private function repo(): RolePermissionRepositoryInterface
    {
        return app(RolePermissionRepositoryInterface::class);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(BazaRolePermissionsSeeder::class);
    }

    public function test_default_matrix_grants_expected(): void
    {
        $repo = $this->repo();

        // shift_chief: впуск + отчёт + состав + закрытие + финансы; НЕ sync/rbac/remove
        self::assertTrue($repo->can(ShiftRole::SHIFT_CHIEF, ShiftPermission::REPORT_VIEW));
        self::assertTrue($repo->can(ShiftRole::SHIFT_CHIEF, ShiftPermission::SHIFT_COMPOSE));
        self::assertTrue($repo->can(ShiftRole::SHIFT_CHIEF, ShiftPermission::FINANCE_VIEW));
        self::assertFalse($repo->can(ShiftRole::SHIFT_CHIEF, ShiftPermission::SYNC_MANAGE));
        self::assertFalse($repo->can(ShiftRole::SHIFT_CHIEF, ShiftPermission::RBAC_MANAGE));
        self::assertFalse($repo->can(ShiftRole::SHIFT_CHIEF, ShiftPermission::SHIFT_REMOVE));

        // ticketer: только впуск
        self::assertTrue($repo->can(ShiftRole::TICKETER, ShiftPermission::TICKET_ENTER));
        self::assertFalse($repo->can(ShiftRole::TICKETER, ShiftPermission::REPORT_VIEW));

        // kpp_commandant: впуск + финансы
        self::assertTrue($repo->can(ShiftRole::KPP_COMMANDANT, ShiftPermission::FINANCE_VIEW));
        self::assertFalse($repo->can(ShiftRole::KPP_COMMANDANT, ShiftPermission::REPORT_VIEW));

        // guard: только впуск
        self::assertTrue($repo->can(ShiftRole::GUARD, ShiftPermission::TICKET_ENTER));
        self::assertFalse($repo->can(ShiftRole::GUARD, ShiftPermission::FINANCE_VIEW));
    }

    public function test_administrator_is_superrole(): void
    {
        $repo = $this->repo();

        // суперроль: всё разрешено, даже sync/rbac, которых нет в таблице
        self::assertTrue($repo->can(ShiftRole::ADMINISTRATOR, ShiftPermission::SYNC_MANAGE));
        self::assertTrue($repo->can(ShiftRole::ADMINISTRATOR, ShiftPermission::RBAC_MANAGE));
        self::assertSame(
            0,
            BazaRolePermissionModel::where('role', ShiftRole::ADMINISTRATOR)->count(),
            'administrator не хранит прав в таблице'
        );
    }

    public function test_can_access_service_check(): void
    {
        $svc = app(CanAccess::class);

        self::assertTrue($svc->check(ShiftRole::TICKETER, ShiftPermission::TICKET_ENTER));
        self::assertFalse($svc->check(ShiftRole::TICKETER, ShiftPermission::SYNC_MANAGE));
        self::assertTrue($svc->check(ShiftRole::ADMINISTRATOR, ShiftPermission::RBAC_MANAGE));
    }

    public function test_set_matrix_replaces_role_permissions(): void
    {
        $repo = $this->repo();

        $repo->setMatrix(ShiftRole::TICKETER, [ShiftPermission::REPORT_VIEW]);

        self::assertTrue($repo->can(ShiftRole::TICKETER, ShiftPermission::REPORT_VIEW), 'новое право выдано');
        self::assertFalse($repo->can(ShiftRole::TICKETER, ShiftPermission::TICKET_ENTER), 'старые права заменены');
    }

    public function test_set_matrix_ignores_administrator(): void
    {
        $repo = $this->repo();

        $repo->setMatrix(ShiftRole::ADMINISTRATOR, []); // попытка отнять права у суперроли

        self::assertTrue($repo->can(ShiftRole::ADMINISTRATOR, ShiftPermission::RBAC_MANAGE), 'суперроль не редактируется');
        self::assertSame(0, BazaRolePermissionModel::where('role', ShiftRole::ADMINISTRATOR)->count());
    }

    public function test_set_matrix_filters_invalid_actions(): void
    {
        $repo = $this->repo();

        $repo->setMatrix(ShiftRole::GUARD, ['bogus.action', ShiftPermission::TICKET_ENTER, 'drop.table']);

        self::assertTrue($repo->can(ShiftRole::GUARD, ShiftPermission::TICKET_ENTER));
        self::assertFalse($repo->can(ShiftRole::GUARD, 'bogus.action'));
        self::assertSame(1, BazaRolePermissionModel::where('role', ShiftRole::GUARD)->count(), 'только валидное действие');
    }

    public function test_get_matrix_structure(): void
    {
        $matrix = $this->repo()->getMatrix();

        self::assertArrayHasKey(ShiftRole::TICKETER, $matrix);
        self::assertContains(ShiftPermission::TICKET_ENTER, $matrix[ShiftRole::TICKETER]);
        self::assertArrayNotHasKey(ShiftRole::ADMINISTRATOR, $matrix, 'суперроль не в матрице');
    }
}
