<?php

declare(strict_types=1);

namespace Tests\Feature\Festival;

use App\Models\ElTicketsModel;
use App\Models\FestivalModel;
use App\Models\User;
use Baza\Shared\Services\DefineService;
use Database\Seeders\BazaRolePermissionsSeeder;
use Database\Seeders\ChangesTestDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * PR-3 (TD-48): изоляция скан/впуск по фестивалю смены за флагом baza.festival_isolation.
 *
 *  - OFF (дефолт): поведение как сегодня — фильтр по дефолтному фестивалю, чужой → «не найден»;
 *  - ON: скан находит билет чужого фестиваля и ставит festival_mismatch (жёлтый), впуск чужого
 *    заблокирован; право entry.override_festival + override=1 (administrator) снимает фильтр.
 *
 * БД baza_test. Смена сидера живёт на FESTIVAL_ID (= config default).
 */
class ScanFestivalIsolationTest extends TestCase
{
    use RefreshDatabase;

    private const SHIFT_FESTIVAL = ChangesTestDataSeeder::FESTIVAL_ID;
    private const FOREIGN_FESTIVAL = '55555555-5555-5555-5555-555555555555';

    private const U_OWN = '11111111-1111-1111-1111-111111111111';
    private const K_OWN = 770077;
    private const U_FOREIGN = '99999999-9999-9999-9999-999999999999';
    private const K_FOREIGN = 880088;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $this->seed(ChangesTestDataSeeder::class);     // admin id=1 + открытая смена на SHIFT_FESTIVAL
        $this->seed(BazaRolePermissionsSeeder::class);

        FestivalModel::create(['id' => self::SHIFT_FESTIVAL, 'name' => 'Осень', 'year' => 2026, 'active' => true, 'active_for_kpp' => true]);
        FestivalModel::create(['id' => self::FOREIGN_FESTIVAL, 'name' => 'Лес', 'year' => 2026, 'active' => true, 'active_for_kpp' => true]);
    }

    private function createEl(string $uuid, int $kilter, string $festivalId): void
    {
        DB::table('el_tickets')->insert([
            'kilter' => $kilter,
            'uuid' => $uuid,
            'city' => 'Москва',
            'name' => 'Тест Гость',
            'email' => 'guest@example.com',
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

    private function scanLink(string $uuid): string
    {
        // DefineService::ELECTRON_TICKET_URL = '/newTickets/'; после str_replace остаётся чистый uuid.
        return '/newTickets/' . $uuid;
    }

    // ───────────────────────── OFF: поведение как сегодня ─────────────────────────

    public function test_off_scan_foreign_returns_not_found(): void
    {
        // isolation OFF (дефолт) → фильтр по дефолтному фестивалю → чужой билет не найден.
        $this->createEl(self::U_FOREIGN, self::K_FOREIGN, self::FOREIGN_FESTIVAL);

        $this->actingAs(User::find(1))
            ->postJson('/api/scan', ['search' => $this->scanLink(self::U_FOREIGN)])
            ->assertStatus(422);
    }

    public function test_off_scan_own_returns_card(): void
    {
        $this->createEl(self::U_OWN, self::K_OWN, self::SHIFT_FESTIVAL);

        $this->actingAs(User::find(1))
            ->postJson('/api/scan', ['search' => $this->scanLink(self::U_OWN)])
            ->assertOk()
            ->assertJson(['kilter' => self::K_OWN]);
    }

    // ───────────────────────── ON: изоляция ─────────────────────────

    public function test_on_scan_foreign_marks_mismatch(): void
    {
        config(['baza.festival_isolation' => true]);
        $this->createEl(self::U_FOREIGN, self::K_FOREIGN, self::FOREIGN_FESTIVAL);

        $this->actingAs(User::find(1))
            ->postJson('/api/scan', ['search' => $this->scanLink(self::U_FOREIGN)])
            ->assertOk()
            ->assertJson([
                'festival_mismatch' => true,
                'ticket_festival_name' => 'Лес',
                'shift_festival_name' => 'Осень',
            ]);
    }

    public function test_on_scan_own_no_mismatch(): void
    {
        config(['baza.festival_isolation' => true]);
        $this->createEl(self::U_OWN, self::K_OWN, self::SHIFT_FESTIVAL);

        $this->actingAs(User::find(1))
            ->postJson('/api/scan', ['search' => $this->scanLink(self::U_OWN)])
            ->assertOk()
            ->assertJson(['festival_mismatch' => false]);
    }

    public function test_on_enter_foreign_is_blocked(): void
    {
        config(['baza.festival_isolation' => true]);
        $this->createEl(self::U_FOREIGN, self::K_FOREIGN, self::FOREIGN_FESTIVAL);

        $this->actingAs(User::find(1))
            ->postJson('/api/enter', ['type' => DefineService::ELECTRON_TICKET, 'id' => self::K_FOREIGN])
            ->assertStatus(422);

        self::assertNull(ElTicketsModel::whereKilter(self::K_FOREIGN)->first()->date_change);
    }

    public function test_on_enter_foreign_allowed_with_override(): void
    {
        config(['baza.festival_isolation' => true]);
        $this->createEl(self::U_FOREIGN, self::K_FOREIGN, self::FOREIGN_FESTIVAL);

        // admin id=1 = administrator (суперроль) → имеет entry.override_festival.
        // Передаём festival_id билета → сервер скоупит впуск им (без коллизии kilter).
        $this->actingAs(User::find(1))
            ->postJson('/api/enter', [
                'type' => DefineService::ELECTRON_TICKET,
                'id' => self::K_FOREIGN,
                'override' => 1,
                'festival_id' => self::FOREIGN_FESTIVAL,
            ])
            ->assertOk();

        self::assertNotNull(ElTicketsModel::whereKilter(self::K_FOREIGN)->first()->date_change);
    }

    public function test_on_enter_own_is_allowed(): void
    {
        config(['baza.festival_isolation' => true]);
        $this->createEl(self::U_OWN, self::K_OWN, self::SHIFT_FESTIVAL);

        $this->actingAs(User::find(1))
            ->postJson('/api/enter', ['type' => DefineService::ELECTRON_TICKET, 'id' => self::K_OWN])
            ->assertOk();

        self::assertNotNull(ElTicketsModel::whereKilter(self::K_OWN)->first()->date_change);
    }

    public function test_on_enter_blocked_when_shift_festival_empty(): void
    {
        // fail-closed: легаси-смена без festival_id (симулируем пустой) при ON → впуск НЕ
        // привязывается молча к дефолтному фестивалю, а блокируется (useNone).
        config(['baza.festival_isolation' => true]);
        DB::table('changes')->whereNull('end')->update(['festival_id' => '']);
        $this->createEl(self::U_OWN, self::K_OWN, self::SHIFT_FESTIVAL);

        $this->actingAs(User::find(1))
            ->postJson('/api/enter', ['type' => DefineService::ELECTRON_TICKET, 'id' => self::K_OWN])
            ->assertStatus(422);

        self::assertNull(ElTicketsModel::whereKilter(self::K_OWN)->first()->date_change);
    }
}
