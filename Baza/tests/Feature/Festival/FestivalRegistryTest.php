<?php

declare(strict_types=1);

namespace Tests\Feature\Festival;

use App\Models\FestivalModel;
use App\Models\User;
use Baza\Festival\Repositories\FestivalRepositoryInterface;
use Baza\Shared\Domain\ValueObject\ShiftRole;
use Database\Seeders\BazaRolePermissionsSeeder;
use Database\Seeders\ChangesTestDataSeeder;
use Database\Seeders\FestivalSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Реестр фестивалей на Vhod (TD-48, PR-1): /api/festivals.
 *
 * Выбор фестиваля для смены (index) — право shift.compose; управление реестром
 * (registry/setActive) — festival.manage (по умолчанию только administrator). БД baza_test.
 */
class FestivalRegistryTest extends TestCase
{
    use RefreshDatabase;

    private const F_AUTUMN = '11111111-1111-1111-1111-111111111111';
    private const F_ARCHIVE = '22222222-2222-2222-2222-222222222222';
    private const F_UNKNOWN = '99999999-9999-9999-9999-999999999999';

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $this->seed(ChangesTestDataSeeder::class);     // admin id=1
        $this->seed(BazaRolePermissionsSeeder::class); // shift_chief: compose; ticketer — нет; festival.manage — ни у кого (только administrator)
    }

    private function userWithRole(string $role): User
    {
        $u = User::factory()->create();
        DB::table('users')->where('id', $u->id)->update(['role' => $role, 'is_admin' => false]);

        return User::find($u->id);
    }

    private function festival(string $id, string $name, bool $activeForKpp = true): FestivalModel
    {
        return FestivalModel::create([
            'id' => $id,
            'name' => $name,
            'year' => 2026,
            'active' => true,
            'active_for_kpp' => $activeForKpp,
        ]);
    }

    // ───────────────────────── Репозиторий + сидер ─────────────────────────

    public function test_seeder_bootstraps_default_festival_idempotently(): void
    {
        $id = (string) config('baza.default_festival_id');

        $this->seed(FestivalSeeder::class);
        $this->seed(FestivalSeeder::class); // повтор не плодит дубль

        $repo = app(FestivalRepositoryInterface::class);
        self::assertTrue($repo->exists($id));
        self::assertNotEmpty($repo->nameFor($id));
        self::assertCount(1, FestivalModel::where('id', $id)->get());
    }

    public function test_repository_filters_active_for_kpp_and_toggles(): void
    {
        $this->festival(self::F_AUTUMN, 'Осень', true);
        $this->festival(self::F_ARCHIVE, 'Архив', false);

        $repo = app(FestivalRepositoryInterface::class);

        self::assertCount(2, $repo->all());

        $active = $repo->listActiveForKpp();
        self::assertCount(1, $active);
        self::assertSame('Осень', $active[0]['name']);

        $names = $repo->namesByIds([self::F_AUTUMN, self::F_ARCHIVE]);
        self::assertSame('Осень', $names[self::F_AUTUMN]);
        self::assertSame('Архив', $names[self::F_ARCHIVE]);

        // включаем «Архив» для КПП → попадает в выбор
        self::assertTrue($repo->setActiveForKpp(self::F_ARCHIVE, true));
        self::assertCount(2, $repo->listActiveForKpp());

        // несуществующий → false (для 404 вместо 500)
        self::assertFalse($repo->setActiveForKpp(self::F_UNKNOWN, true));
    }

    // ───────────────────────── API: выбор для смены ─────────────────────────

    public function test_list_requires_authentication(): void
    {
        $this->getJson('/api/festivals')->assertUnauthorized();
    }

    public function test_ticketer_forbidden_to_list(): void
    {
        $this->actingAs($this->userWithRole(ShiftRole::TICKETER))
            ->getJson('/api/festivals')
            ->assertStatus(403);
    }

    public function test_chief_sees_only_active_for_kpp(): void
    {
        $this->festival(self::F_AUTUMN, 'Осень', true);
        $this->festival(self::F_ARCHIVE, 'Архив', false);

        $res = $this->actingAs($this->userWithRole(ShiftRole::SHIFT_CHIEF))
            ->getJson('/api/festivals')
            ->assertOk()
            ->assertJson(['success' => true]);

        $fests = $res->json('festivals');
        self::assertCount(1, $fests);
        self::assertSame('Осень', $fests[0]['name']);
    }

    // ───────────────────────── API: управление реестром ─────────────────────────

    public function test_registry_management_requires_festival_manage_permission(): void
    {
        // shift_chief имеет shift.compose, но НЕ festival.manage → 403.
        $chief = $this->userWithRole(ShiftRole::SHIFT_CHIEF);
        $this->actingAs($chief)->getJson('/api/festivals/registry')->assertStatus(403);
        $this->actingAs($chief)->postJson('/api/festivals/'.self::F_ARCHIVE.'/active', ['active' => true])
            ->assertStatus(403);
    }

    public function test_admin_manages_registry_and_toggle_reflects_in_selector(): void
    {
        $this->festival(self::F_ARCHIVE, 'Архив', false);
        $admin = User::find(1);

        // весь реестр виден (включая скрытый с КПП)
        $this->actingAs($admin)->getJson('/api/festivals/registry')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Архив']);

        // до включения — в выборе смены пусто
        self::assertCount(0, $this->actingAs($admin)->getJson('/api/festivals')->json('festivals'));

        // включаем «Архив» для КПП
        $this->actingAs($admin)->postJson('/api/festivals/'.self::F_ARCHIVE.'/active', ['active' => true])
            ->assertOk()
            ->assertJson(['success' => true]);

        // теперь появился в выборе смены
        $names = array_column($this->actingAs($admin)->getJson('/api/festivals')->json('festivals'), 'name');
        self::assertContains('Архив', $names);
    }

    public function test_toggle_unknown_festival_returns_404(): void
    {
        $this->actingAs(User::find(1))
            ->postJson('/api/festivals/'.self::F_UNKNOWN.'/active', ['active' => true])
            ->assertStatus(404);
    }
}
