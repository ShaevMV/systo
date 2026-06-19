<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\User;
use Baza\Shared\Domain\ValueObject\ShiftRole;
use Database\Seeders\BazaRolePermissionsSeeder;
use Database\Seeders\UsersTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Тесты permission-middleware на admin-роутах смен/синхронизации (Baza, Ф2 PR-5).
 *
 * /report,/change/*,/sync/* переведены с бинарного 'admin' на ['auth','permission:<действие>'].
 * administrator (is_admin=true → суперроль) проходит везде — вход админов цел.
 * БД baza_test. UsersTableSeeder: id=1 admin, id=3 обычный. BazaRolePermissionsSeeder — матрица.
 */
class PermissionMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(UsersTableSeeder::class);
        $this->seed(BazaRolePermissionsSeeder::class);
    }

    public function test_guest_redirected_to_login_on_report(): void
    {
        $this->get('/report')->assertRedirect('/login');
    }

    public function test_admin_can_open_report(): void
    {
        // is_admin=true → administrator (суперроль) → доступ цел
        $this->actingAs(User::find(1))->get('/report')->assertOk();
    }

    public function test_admin_can_open_sync(): void
    {
        $this->actingAs(User::find(1))->get('/sync')->assertOk();
    }

    public function test_ticketer_forbidden_on_sync(): void
    {
        // user 3: is_admin=false, role=null → ticketer; sync.manage нет в дефолт-матрице
        $this->actingAs(User::find(3))->get('/sync')->assertForbidden();
    }

    public function test_ticketer_forbidden_on_change_save(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);

        $this->actingAs(User::find(3))
            ->post('/change/save', ['compound' => [3], 'start' => now()->toDateTimeString()])
            ->assertForbidden();
    }

    public function test_shift_chief_can_open_report(): void
    {
        // обычному сотруднику явно дали роль начальника смены → есть report.view
        $user = User::find(3);
        $user->role = ShiftRole::SHIFT_CHIEF;
        $user->save();

        $this->actingAs($user)->get('/report')->assertOk();
    }

    public function test_shift_chief_forbidden_on_sync(): void
    {
        // у начальника смены нет sync.manage (только у administrator)
        $user = User::find(3);
        $user->role = ShiftRole::SHIFT_CHIEF;
        $user->save();

        $this->actingAs($user)->get('/sync')->assertForbidden();
    }
}
