<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\ChangesModel;
use App\Models\ChangeUserModel;
use App\Models\User;
use Baza\Changes\Applications\SaveChange\SaveChange;
use Baza\Changes\Repositories\ChangesRepositoryInterface;
use Baza\Shared\Domain\ValueObject\ShiftRole;
use Carbon\Carbon;
use Database\Seeders\BazaRolePermissionsSeeder;
use Database\Seeders\UsersTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Тесты начальника смены + инвариант «у смены есть начальник» (Baza, Ф2 PR-7).
 *
 * Выбранный chiefId → роль shift_chief в change_user; остальные — производная.
 * Непустая смена без главного (или с главным вне состава) — DomainException;
 * через форму — мягкий redirect с ошибкой, не 500. БД baza_test.
 */
class ShiftChiefTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(UsersTableSeeder::class);
    }

    public function test_save_sets_chief_role_in_change_user(): void
    {
        app(SaveChange::class)->save([1, 3, 4], Carbon::now(), null, 3); // chief = id 3

        $change = ChangesModel::query()->latest('id')->first();
        $rows = ChangeUserModel::where('change_id', $change->id)->get()->keyBy('user_id');

        self::assertSame(ShiftRole::SHIFT_CHIEF, $rows[3]->role, 'выбранный главный → shift_chief');
        self::assertSame(ShiftRole::ADMINISTRATOR, $rows[1]->role, 'admin → administrator (производная)');
        self::assertSame(ShiftRole::TICKETER, $rows[4]->role, 'обычный → ticketer (производная)');
    }

    public function test_save_chief_not_in_members_throws(): void
    {
        $this->expectException(\DomainException::class);

        app(SaveChange::class)->save([3, 4], Carbon::now(), null, 99); // главный вне состава
    }

    public function test_get_chief_id_returns_chief(): void
    {
        app(SaveChange::class)->save([1, 3, 4], Carbon::now(), null, 3);
        $change = ChangesModel::query()->latest('id')->first();

        self::assertSame(3, app(ChangesRepositoryInterface::class)->getChiefId($change->id));
    }

    public function test_controller_save_without_chief_redirects_with_error_not_500(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->seed(BazaRolePermissionsSeeder::class);

        // admin (суперроль) проходит permission:shift.compose, но без главного —
        // мягкий redirect с ошибкой, смена не создаётся (не 500).
        $this->actingAs(User::find(1))
            ->post('/change/save', ['compound' => [1, 3], 'start' => now()->toDateTimeString()])
            ->assertSessionHas('shift_error');

        self::assertSame(0, ChangesModel::count(), 'смена без главного не создана');
    }

    public function test_controller_save_with_chief_creates_shift(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->seed(BazaRolePermissionsSeeder::class);

        $this->actingAs(User::find(1))
            ->post('/change/save', ['compound' => [1, 3], 'start' => now()->toDateTimeString(), 'chief' => 3])
            ->assertRedirect(route('changes.report'));

        self::assertSame(1, ChangesModel::count());
        $change = ChangesModel::query()->latest('id')->first();
        self::assertSame(3, app(ChangesRepositoryInterface::class)->getChiefId($change->id), 'главный сохранён');
    }
}
