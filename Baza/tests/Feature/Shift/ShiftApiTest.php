<?php

declare(strict_types=1);

namespace Tests\Feature\Shift;

use App\Models\User;
use Baza\Changes\Applications\SaveChange\SaveChange;
use Baza\Changes\Repositories\ChangesRepositoryInterface;
use Baza\Shared\Domain\ValueObject\ShiftRole;
use Carbon\Carbon;
use Database\Seeders\BazaRolePermissionsSeeder;
use Database\Seeders\ChangesTestDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Шаг 6: управление сменами из PWA — /api/shifts. Изоляция начальника (видит/закрывает
 * только свою смену), administrator — все. Доступ shift.compose/shift.close. БД baza_test.
 */
class ShiftApiTest extends TestCase
{
    use RefreshDatabase;

    private const URL = '/api/shifts';

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $this->seed(ChangesTestDataSeeder::class);     // admin id=1
        $this->seed(BazaRolePermissionsSeeder::class); // shift_chief: compose/close; ticketer — нет
    }

    private function userWithRole(string $role): User
    {
        $u = User::factory()->create();
        DB::table('users')->where('id', $u->id)->update(['role' => $role, 'is_admin' => false]);

        return User::find($u->id);
    }

    /** Открыть смену боевым путём (свежий SaveChange — обход singleton ChangesModel). */
    private function openShift(int $chiefId, array $members): void
    {
        app(SaveChange::class)->save($members, Carbon::now(), null, $chiefId);
    }

    public function test_requires_authentication(): void
    {
        $this->getJson(self::URL)->assertUnauthorized();
    }

    public function test_ticketer_forbidden(): void
    {
        $this->actingAs($this->userWithRole(ShiftRole::TICKETER))->getJson(self::URL)->assertStatus(403);
    }

    public function test_admin_sees_all_shifts(): void
    {
        $a = $this->userWithRole(ShiftRole::SHIFT_CHIEF);
        $b = $this->userWithRole(ShiftRole::SHIFT_CHIEF);
        $this->openShift($a->id, [$a->id]);
        $this->openShift($b->id, [$b->id]);

        $res = $this->actingAs(User::find(1))->getJson(self::URL)->assertOk()->assertJson(['is_admin' => true]);
        // минимум 2 созданные смены (плюс сид-смена)
        self::assertGreaterThanOrEqual(2, count($res->json('shifts')));
    }

    public function test_chief_sees_only_own_shift(): void
    {
        $a = $this->userWithRole(ShiftRole::SHIFT_CHIEF);
        $b = $this->userWithRole(ShiftRole::SHIFT_CHIEF);
        $this->openShift($a->id, [$a->id]);
        $this->openShift($b->id, [$b->id]);

        $res = $this->actingAs($a)->getJson(self::URL)->assertOk()->assertJson(['is_admin' => false]);
        $shifts = $res->json('shifts');
        self::assertCount(1, $shifts, 'начальник видит только свою смену');
        self::assertSame($a->id, $shifts[0]['chief_id']);
    }

    public function test_admin_creates_shift_with_chief(): void
    {
        $chief = $this->userWithRole(ShiftRole::SHIFT_CHIEF);
        $member = $this->userWithRole(ShiftRole::TICKETER);

        $this->actingAs(User::find(1))->postJson(self::URL, [
            'members' => [$member->id],
            'chief_id' => $chief->id,
        ])->assertOk()->assertJson(['success' => true]);

        // Смена этого начальника открыта (в составе + роль shift_chief).
        $changeId = app(ChangesRepositoryInterface::class)->getChangeId($chief->id);
        self::assertNotNull($changeId);
        self::assertSame($chief->id, app(ChangesRepositoryInterface::class)->getChiefId($changeId));
    }

    public function test_admin_create_requires_chief(): void
    {
        $member = $this->userWithRole(ShiftRole::TICKETER);
        $this->actingAs(User::find(1))->postJson(self::URL, ['members' => [$member->id]])
            ->assertStatus(422);
    }

    public function test_chief_creates_own_shift(): void
    {
        $chief = $this->userWithRole(ShiftRole::SHIFT_CHIEF);
        $member = $this->userWithRole(ShiftRole::GUARD);

        $this->actingAs($chief)->postJson(self::URL, ['members' => [$member->id]])
            ->assertOk()->assertJson(['success' => true]);

        // Начальник = он сам (chief_id из тела игнорируется, берётся Auth::id()).
        $changeId = app(ChangesRepositoryInterface::class)->getChangeId($chief->id);
        self::assertSame($chief->id, app(ChangesRepositoryInterface::class)->getChiefId($changeId));
    }

    public function test_chief_cannot_close_foreign_shift(): void
    {
        $a = $this->userWithRole(ShiftRole::SHIFT_CHIEF);
        $b = $this->userWithRole(ShiftRole::SHIFT_CHIEF);
        $this->openShift($b->id, [$b->id]);
        $foreignId = app(ChangesRepositoryInterface::class)->getChangeId($b->id);

        $this->actingAs($a)->postJson(self::URL.'/'.$foreignId.'/close')->assertStatus(403);
    }

    public function test_chief_closes_own_shift(): void
    {
        $a = $this->userWithRole(ShiftRole::SHIFT_CHIEF);
        $this->openShift($a->id, [$a->id]);
        $ownId = app(ChangesRepositoryInterface::class)->getChangeId($a->id);

        $this->actingAs($a)->postJson(self::URL.'/'.$ownId.'/close')->assertOk()->assertJson(['success' => true]);
        // Закрыта → больше не «текущая».
        self::assertNull(app(ChangesRepositoryInterface::class)->getChangeId($a->id));
    }
}
