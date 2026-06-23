<?php

declare(strict_types=1);

namespace Tests\Feature\Festival;

use App\Models\ChangesModel;
use App\Models\FestivalModel;
use App\Models\User;
use Baza\Changes\Repositories\ChangesRepositoryInterface;
use Baza\Shared\Domain\ValueObject\ShiftRole;
use Database\Seeders\BazaRolePermissionsSeeder;
use Database\Seeders\ChangesTestDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * PR-2 (TD-48): смена несёт фестиваль. Выбор при открытии смены — авто-выбор единственного
 * активного / обязателен при нескольких / валидация active_for_kpp / fallback на дефолт при
 * пустом реестре. /api/shifts. БД baza_test.
 */
class ShiftFestivalTest extends TestCase
{
    use RefreshDatabase;

    private const URL = '/api/shifts';
    private const F2 = '22222222-2222-2222-2222-222222222222';
    private const F3 = '33333333-3333-3333-3333-333333333333';
    private const F_INACTIVE = '44444444-4444-4444-4444-444444444444';

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $this->seed(ChangesTestDataSeeder::class);     // admin id=1
        $this->seed(BazaRolePermissionsSeeder::class); // shift_chief: compose/close
    }

    private function userWithRole(string $role): User
    {
        $u = User::factory()->create();
        DB::table('users')->where('id', $u->id)->update(['role' => $role, 'is_admin' => false]);

        return User::find($u->id);
    }

    private function festival(string $id, string $name, bool $activeForKpp = true): void
    {
        FestivalModel::create([
            'id' => $id,
            'name' => $name,
            'year' => 2026,
            'active' => true,
            'active_for_kpp' => $activeForKpp,
        ]);
    }

    private function festivalIdOfShift(int $chiefId): ?string
    {
        $changeId = app(ChangesRepositoryInterface::class)->getChangeId($chiefId);

        return $changeId === null ? null : (string) ChangesModel::find($changeId)->festival_id;
    }

    public function test_store_defaults_to_config_when_registry_empty(): void
    {
        // Реестр пуст → fallback на config('baza.default_festival_id') (обратная совместимость).
        $chief = $this->userWithRole(ShiftRole::SHIFT_CHIEF);

        $this->actingAs(User::find(1))->postJson(self::URL, [
            'members' => [$chief->id],
            'chief_id' => $chief->id,
        ])->assertOk()->assertJson(['success' => true]);

        self::assertSame((string) config('baza.default_festival_id'), $this->festivalIdOfShift($chief->id));
    }

    public function test_store_auto_selects_single_active_festival(): void
    {
        $this->festival(self::F2, 'Осень', true);
        $chief = $this->userWithRole(ShiftRole::SHIFT_CHIEF);

        $this->actingAs(User::find(1))->postJson(self::URL, [
            'members' => [$chief->id],
            'chief_id' => $chief->id,
        ])->assertOk()->assertJson(['success' => true]);

        self::assertSame(self::F2, $this->festivalIdOfShift($chief->id));
    }

    public function test_store_requires_festival_when_multiple_active(): void
    {
        $this->festival(self::F2, 'Осень', true);
        $this->festival(self::F3, 'Лес', true);
        $chief = $this->userWithRole(ShiftRole::SHIFT_CHIEF);

        $this->actingAs(User::find(1))->postJson(self::URL, [
            'members' => [$chief->id],
            'chief_id' => $chief->id,
        ])->assertStatus(422)->assertJson(['success' => false]);

        // смена не создана
        self::assertNull($this->festivalIdOfShift($chief->id));
    }

    public function test_store_accepts_explicit_active_festival(): void
    {
        $this->festival(self::F2, 'Осень', true);
        $this->festival(self::F3, 'Лес', true);
        $chief = $this->userWithRole(ShiftRole::SHIFT_CHIEF);

        $this->actingAs(User::find(1))->postJson(self::URL, [
            'members' => [$chief->id],
            'chief_id' => $chief->id,
            'festival_id' => self::F3,
        ])->assertOk()->assertJson(['success' => true]);

        self::assertSame(self::F3, $this->festivalIdOfShift($chief->id));
    }

    public function test_store_rejects_inactive_festival(): void
    {
        $this->festival(self::F_INACTIVE, 'Архив', false);
        $chief = $this->userWithRole(ShiftRole::SHIFT_CHIEF);

        $this->actingAs(User::find(1))->postJson(self::URL, [
            'members' => [$chief->id],
            'chief_id' => $chief->id,
            'festival_id' => self::F_INACTIVE,
        ])->assertStatus(422)->assertJson(['success' => false]);

        self::assertNull($this->festivalIdOfShift($chief->id));
    }

    public function test_list_open_returns_festival_info(): void
    {
        $this->festival(self::F2, 'Осень', true);
        $chief = $this->userWithRole(ShiftRole::SHIFT_CHIEF);

        $this->actingAs($chief)->postJson(self::URL, ['members' => [$chief->id]])
            ->assertOk();

        $shifts = $this->actingAs(User::find(1))->getJson(self::URL)->assertOk()->json('shifts');
        $mine = collect($shifts)->firstWhere('chief_id', $chief->id);

        self::assertNotNull($mine);
        self::assertSame(self::F2, $mine['festival_id']);
        self::assertSame('Осень', $mine['festival_name']);
    }
}
