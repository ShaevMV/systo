<?php

declare(strict_types=1);

namespace Tests\Feature\Festival;

use App\Models\FestivalModel;
use App\Models\User;
use Baza\Changes\Applications\SaveChange\SaveChange;
use Baza\Shared\Domain\ValueObject\ShiftRole;
use Carbon\Carbon;
use Database\Seeders\BazaRolePermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * PR-4 (TD-48): изоляция поиска без QR по фестивалю смены за флагом baza.festival_isolation.
 *
 * Смена сотрудника на фестивале F_A (≠ дефолтного), билеты заведены в F_A и в дефолтном.
 *  - OFF: поиск по дефолтному фестивалю (прежнее поведение) — находит билет дефолта;
 *  - ON: поиск ограничен фестивалём смены (F_A) — находит только билет F_A + festival_scope.
 *
 * БД baza_test. /api/search?q=.
 */
class SearchFestivalIsolationTest extends TestCase
{
    use RefreshDatabase;

    private const F_A = 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa';
    private const K_A = 700001;
    private const K_DEFAULT = 700002;

    private string $fDefault;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $this->seed(BazaRolePermissionsSeeder::class);

        $this->fDefault = (string) config('baza.default_festival_id');

        FestivalModel::create(['id' => self::F_A, 'name' => 'Осень', 'year' => 2026, 'active' => true, 'active_for_kpp' => true]);
        FestivalModel::create(['id' => $this->fDefault, 'name' => 'Дефолт', 'year' => 2026, 'active' => true, 'active_for_kpp' => true]);
    }

    private function chiefOnFestival(string $festivalId): User
    {
        $u = User::factory()->create();
        DB::table('users')->where('id', $u->id)->update(['role' => ShiftRole::SHIFT_CHIEF, 'is_admin' => false]);

        // Открыть смену этого начальника на нужном фестивале (боевым путём SaveChange).
        app(SaveChange::class)->save([$u->id], Carbon::now(), null, $u->id, $festivalId);

        return User::find($u->id);
    }

    private function createEl(int $kilter, string $festivalId): void
    {
        DB::table('el_tickets')->insert([
            'kilter' => $kilter,
            'uuid' => sprintf('%08d-0000-0000-0000-000000000000', $kilter),
            'city' => 'Москва',
            'name' => 'Иван Поиск',
            'email' => 'ivan@example.com',
            'phone' => '+70000000000',
            'comment' => null,
            'date_order' => now(),
            'status' => 'paid',
            'type_ticket' => 'Электронный',
            'type_ticket_id' => '22222222-2222-2222-2222-222222222222',
            'is_need_seedling' => 0,
            'change_id' => null,
            'date_change' => null,
            'festival_id' => $festivalId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** @return int[] kilter'ы найденных электронных билетов */
    private function electronKilters(array $json): array
    {
        return array_map(static fn (array $i): int => (int) $i['kilter'], $json['groups']['electron'] ?? []);
    }

    public function test_off_search_uses_default_festival(): void
    {
        // isolation OFF (дефолт): поиск по дефолтному фестивалю независимо от смены.
        $chief = $this->chiefOnFestival(self::F_A);
        $this->createEl(self::K_A, self::F_A);
        $this->createEl(self::K_DEFAULT, $this->fDefault);

        $json = $this->actingAs($chief)->getJson('/api/search?q=Иван')->assertOk()->json();

        $kilters = $this->electronKilters($json);
        self::assertContains(self::K_DEFAULT, $kilters);
        self::assertNotContains(self::K_A, $kilters);
    }

    public function test_on_search_isolated_to_shift_festival(): void
    {
        config(['baza.festival_isolation' => true]);

        $chief = $this->chiefOnFestival(self::F_A);
        $this->createEl(self::K_A, self::F_A);
        $this->createEl(self::K_DEFAULT, $this->fDefault);

        $json = $this->actingAs($chief)->getJson('/api/search?q=Иван')
            ->assertOk()
            ->assertJson(['festival_scope' => 'Осень'])
            ->json();

        $kilters = $this->electronKilters($json);
        self::assertContains(self::K_A, $kilters);
        self::assertNotContains(self::K_DEFAULT, $kilters);
    }

    public function test_on_no_shift_returns_empty(): void
    {
        // fail-closed: сотрудник без открытой смены при ON ничего не находит (не дефолтный фест).
        config(['baza.festival_isolation' => true]);
        $this->createEl(self::K_A, self::F_A);
        $this->createEl(self::K_DEFAULT, $this->fDefault);

        $noShift = User::factory()->create();
        $json = $this->actingAs($noShift)->getJson('/api/search?q=Иван')->assertOk()->json();

        self::assertSame([], $this->electronKilters($json));
    }
}
