<?php

declare(strict_types=1);

namespace Tests\Feature\Festival;

use App\Models\FestivalModel;
use App\Models\LiveTicketModel;
use App\Models\User;
use Baza\Shared\Services\DefineService;
use Database\Seeders\BazaRolePermissionsSeeder;
use Database\Seeders\ChangesTestDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * PR-6 (TD-48): закрытие дыры живых билетов — изоляция по festival_id.
 *
 * Смена сидера на дефолтном фестивале. Живые билеты: свой (дефолт), чужой (F_FOREIGN),
 * без фестиваля (NULL — пул номеров).
 *  - ON: свой и NULL впускаются, чужой заблокирован;
 *  - OFF: свой и NULL впускаются (поведение сохранено).
 *
 * lenient-фильтр (festival ИЛИ NULL) гарантирует, что непомеченные номера не теряются.
 */
class LiveFestivalIsolationTest extends TestCase
{
    use RefreshDatabase;

    private const F_FOREIGN = '66666666-6666-6666-6666-666666666666';

    private const K_OWN = 5001;
    private const K_FOREIGN = 5002;
    private const K_NULL = 5003;

    private string $fDefault;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $this->seed(ChangesTestDataSeeder::class);     // admin id=1 + смена на дефолте
        $this->seed(BazaRolePermissionsSeeder::class);

        $this->fDefault = (string) config('baza.default_festival_id');
        FestivalModel::create(['id' => self::F_FOREIGN, 'name' => 'Лес', 'year' => 2026, 'active' => true, 'active_for_kpp' => true]);

        $this->createLive(self::K_OWN, $this->fDefault);
        $this->createLive(self::K_FOREIGN, self::F_FOREIGN);
        $this->createLive(self::K_NULL, null);
    }

    private function createLive(int $kilter, ?string $festivalId): void
    {
        DB::table('live_tickets')->insert([
            'kilter' => $kilter,
            'change_id' => null,
            'date_change' => null,
            'festival_id' => $festivalId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function enter(int $kilter): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs(User::find(1))
            ->postJson('/api/enter', ['type' => DefineService::LIVE_TICKET, 'id' => $kilter]);
    }

    private function entered(int $kilter): bool
    {
        return LiveTicketModel::whereKilter($kilter)->first()->date_change !== null;
    }

    // ───────────────────────── ON ─────────────────────────

    public function test_on_own_festival_live_is_allowed(): void
    {
        config(['baza.festival_isolation' => true]);
        $this->enter(self::K_OWN)->assertOk();
        self::assertTrue($this->entered(self::K_OWN));
    }

    public function test_on_foreign_festival_live_is_blocked(): void
    {
        config(['baza.festival_isolation' => true]);
        $this->enter(self::K_FOREIGN)->assertStatus(422);
        self::assertFalse($this->entered(self::K_FOREIGN));
    }

    public function test_on_null_festival_live_is_allowed_lenient(): void
    {
        config(['baza.festival_isolation' => true]);
        $this->enter(self::K_NULL)->assertOk();
        self::assertTrue($this->entered(self::K_NULL));
    }

    // ───────────────────────── OFF: поведение сохранено ─────────────────────────

    public function test_off_own_and_null_live_allowed(): void
    {
        // isolation OFF (дефолт): свой (дефолтный фестиваль) и NULL впускаются как раньше.
        $this->enter(self::K_OWN)->assertOk();
        self::assertTrue($this->entered(self::K_OWN));

        $this->enter(self::K_NULL)->assertOk();
        self::assertTrue($this->entered(self::K_NULL));
    }
}
