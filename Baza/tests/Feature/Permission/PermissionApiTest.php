<?php

declare(strict_types=1);

namespace Tests\Feature\Permission;

use App\Models\BazaRolePermissionModel;
use App\Models\User;
use Baza\Shared\Domain\ValueObject\ShiftPermission;
use Baza\Shared\Domain\ValueObject\ShiftRole;
use Database\Seeders\BazaRolePermissionsSeeder;
use Database\Seeders\ChangesTestDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Шаг 4: редактор матрицы прав в новом PWA — /api/permissions/matrix (GET/POST).
 * Доступ только rbac.manage (administrator). БД baza_test (phpunit.xml).
 */
class PermissionApiTest extends TestCase
{
    use RefreshDatabase;

    private const URL = '/api/permissions/matrix';

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $this->seed(ChangesTestDataSeeder::class);     // admin id=1 (rbac.manage по суперроли)
        $this->seed(BazaRolePermissionsSeeder::class); // дефолт-матрица
    }

    private function userWithRole(string $role): User
    {
        $u = User::factory()->create();
        DB::table('users')->where('id', $u->id)->update(['role' => $role, 'is_admin' => false]);

        return User::find($u->id);
    }

    public function test_requires_authentication(): void
    {
        $this->getJson(self::URL)->assertUnauthorized();
    }

    public function test_admin_gets_matrix(): void
    {
        $res = $this->actingAs(User::find(1))->getJson(self::URL)
            ->assertOk()
            ->assertJson(['success' => true]);

        self::assertNotEmpty($res->json('roles'));
        self::assertNotEmpty($res->json('actions'));
        self::assertSame(ShiftRole::ADMINISTRATOR, $res->json('admin_role'));
        // ticketer в дефолте имеет ticket.scan
        self::assertContains(ShiftPermission::TICKET_SCAN, $res->json('matrix.'.ShiftRole::TICKETER));
    }

    public function test_non_rbac_manager_forbidden(): void
    {
        $this->actingAs($this->userWithRole(ShiftRole::TICKETER))->getJson(self::URL)
            ->assertStatus(403);
    }

    public function test_admin_saves_matrix_form_is_source_of_truth(): void
    {
        // Перезаписываем ticketer только на ticket.scan (форма = источник правды → ticket.enter снимается).
        $this->actingAs(User::find(1))->postJson(self::URL, [
            'perm' => [ShiftRole::TICKETER => [ShiftPermission::TICKET_SCAN]],
        ])->assertOk()->assertJson(['success' => true]);

        self::assertTrue(
            BazaRolePermissionModel::where('role', ShiftRole::TICKETER)->where('action', ShiftPermission::TICKET_SCAN)->exists()
        );
        self::assertFalse(
            BazaRolePermissionModel::where('role', ShiftRole::TICKETER)->where('action', ShiftPermission::TICKET_ENTER)->exists(),
            'неотмеченное право должно сняться (форма = источник правды)'
        );
    }

    public function test_administrator_not_editable(): void
    {
        // Попытка задать administrator пустой набор игнорируется (суперроль).
        $this->actingAs(User::find(1))->postJson(self::URL, [
            'perm' => [ShiftRole::ADMINISTRATOR => []],
        ])->assertOk();

        // administrator в таблицу не пишется и остаётся всемогущим.
        self::assertSame(0, BazaRolePermissionModel::where('role', ShiftRole::ADMINISTRATOR)->count());
    }
}
