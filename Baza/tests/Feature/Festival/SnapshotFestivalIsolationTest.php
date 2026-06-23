<?php

declare(strict_types=1);

namespace Tests\Feature\Festival;

use App\Models\FestivalModel;
use App\Models\User;
use Baza\Tickets\Repositories\TicketSearchRepositoryInterface;
use Database\Seeders\BazaRolePermissionsSeeder;
use Database\Seeders\ChangesTestDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PR-7 (TD-48): офлайн-снимок строго по фестивалю смены за флагом baza.festival_isolation.
 *
 *  - ON: снимок = фестиваль ОТКРЫТОЙ СМЕНЫ, клиентский festival_id ИГНОРИРУЕТСЯ
 *        (нельзя выкачать чужой фестиваль на устройство);
 *  - OFF: клиентский festival_id уважается (прежнее поведение).
 *
 * Снимок наполняется из ticket_search. БД baza_test. /api/snapshot.
 */
class SnapshotFestivalIsolationTest extends TestCase
{
    use RefreshDatabase;

    private const F_FOREIGN = '77777777-7777-7777-7777-777777777777';

    private string $fDefault;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $this->seed(ChangesTestDataSeeder::class);     // admin id=1 + смена на дефолте
        $this->seed(BazaRolePermissionsSeeder::class);

        $this->fDefault = (string) config('baza.default_festival_id');
        FestivalModel::create(['id' => $this->fDefault, 'name' => 'Дефолт', 'year' => 2026, 'active' => true, 'active_for_kpp' => true]);
        FestivalModel::create(['id' => self::F_FOREIGN, 'name' => 'Лес', 'year' => 2026, 'active' => true, 'active_for_kpp' => true]);

        $repo = app(TicketSearchRepositoryInterface::class);
        $repo->index(['ticket_uuid' => 'own-0000-0000-0000-000000000001', 'festival_id' => $this->fDefault, 'type' => 'electron', 'kilter' => 101, 'fio' => 'Свой Гость']);
        $repo->index(['ticket_uuid' => 'foreign-0000-0000-0000-00000001', 'festival_id' => self::F_FOREIGN, 'type' => 'electron', 'kilter' => 202, 'fio' => 'Чужой Гость']);
    }

    public function test_on_snapshot_is_shift_festival_only(): void
    {
        config(['baza.festival_isolation' => true]);

        // Клиент просит чужой фестиваль — при изоляции ИГНОРИРУЕТСЯ, отдаём фестиваль смены.
        $this->actingAs(User::find(1))
            ->getJson('/api/snapshot?festival_id=' . self::F_FOREIGN)
            ->assertOk()
            ->assertJson([
                'festival_id' => $this->fDefault,
                'festival_name' => 'Дефолт',
                'count' => 1,
            ]);
    }

    public function test_off_snapshot_respects_client_festival(): void
    {
        // OFF (дефолт): клиентский festival_id уважается.
        $this->actingAs(User::find(1))
            ->getJson('/api/snapshot?festival_id=' . self::F_FOREIGN)
            ->assertOk()
            ->assertJson([
                'festival_id' => self::F_FOREIGN,
                'count' => 1,
            ]);
    }

    public function test_on_no_open_shift_returns_empty_snapshot_not_default(): void
    {
        // fail-closed (HIGH-фикс): сотрудник без открытой смены при ON НЕ должен получить
        // дефолтный фестиваль целиком (ПДн) — снимок пустой.
        config(['baza.festival_isolation' => true]);
        $noShift = User::factory()->create();

        $res = $this->actingAs($noShift)
            ->getJson('/api/snapshot?festival_id=' . $this->fDefault)
            ->assertOk()
            ->assertJson(['festival_id' => null, 'count' => 0]);

        self::assertSame([], $res->json('items'));
    }
}
