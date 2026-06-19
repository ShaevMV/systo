<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\User;
use Baza\Permission\Repositories\RolePermissionRepositoryInterface;
use Baza\Shared\Domain\ValueObject\ShiftPermission;
use Baza\Shared\Domain\ValueObject\ShiftRole;
use Database\Seeders\BazaRolePermissionsSeeder;
use Database\Seeders\UsersTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Тесты экрана-редактора матрицы прав (Baza, Ф2 PR-6).
 *
 * /permissions (GET форма + POST сохранение) под правом rbac.manage (дефолт — admin).
 * Форма — источник правды: снятая галочка убирает право; administrator не редактируется.
 * БД baza_test. UsersTableSeeder: id=1 admin, id=3 обычный.
 */
class PermissionMatrixUiTest extends TestCase
{
    use RefreshDatabase;

    private function repo(): RolePermissionRepositoryInterface
    {
        return app(RolePermissionRepositoryInterface::class);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(UsersTableSeeder::class);
        $this->seed(BazaRolePermissionsSeeder::class);
    }

    public function test_admin_can_open_matrix(): void
    {
        $this->actingAs(User::find(1))->get('/permissions')->assertOk();
    }

    public function test_guest_redirected_to_login(): void
    {
        $this->get('/permissions')->assertRedirect('/login');
    }

    public function test_ticketer_forbidden(): void
    {
        // user 3: is_admin=false → ticketer; rbac.manage нет
        $this->actingAs(User::find(3))->get('/permissions')->assertForbidden();
    }

    public function test_save_updates_matrix(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);

        $this->actingAs(User::find(1))
            ->post('/permissions', [
                'perm' => [
                    ShiftRole::TICKETER => [ShiftPermission::REPORT_VIEW, ShiftPermission::TICKET_ENTER],
                ],
            ])
            ->assertRedirect(route('permission.index'));

        // ticketer получил отмеченное
        self::assertTrue($this->repo()->can(ShiftRole::TICKETER, ShiftPermission::REPORT_VIEW));
        self::assertTrue($this->repo()->can(ShiftRole::TICKETER, ShiftPermission::TICKET_ENTER));
        self::assertFalse($this->repo()->can(ShiftRole::TICKETER, ShiftPermission::SYNC_MANAGE));

        // guard НЕ был в payload → его права сняты (форма = источник правды)
        self::assertFalse($this->repo()->can(ShiftRole::GUARD, ShiftPermission::TICKET_ENTER));
    }

    public function test_save_cannot_grant_to_administrator(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);

        // попытка прописать administrator в матрицу через форму — игнорируется
        $this->actingAs(User::find(1))
            ->post('/permissions', [
                'perm' => [
                    ShiftRole::ADMINISTRATOR => [ShiftPermission::SYNC_MANAGE],
                ],
            ])
            ->assertRedirect();

        // суперроль и так всё может, но строк в таблице у неё нет
        self::assertTrue($this->repo()->can(ShiftRole::ADMINISTRATOR, ShiftPermission::SYNC_MANAGE));
        self::assertSame([], $this->repo()->getMatrix()[ShiftRole::ADMINISTRATOR] ?? []);
    }
}
