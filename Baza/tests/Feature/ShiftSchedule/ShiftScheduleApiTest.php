<?php

declare(strict_types=1);

namespace Tests\Feature\ShiftSchedule;

use App\Models\User;
use Baza\Shared\Domain\ValueObject\ShiftRole;
use Baza\ShiftSchedule\Dto\ShiftScheduleDto;
use Baza\ShiftSchedule\Repositories\ShiftScheduleRepositoryInterface;
use Carbon\Carbon;
use Database\Seeders\BazaRolePermissionsSeeder;
use Database\Seeders\ChangesTestDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * PR-A: плановое расписание смен. CRUD под shift.compose (изоляция начальника),
 * личное расписание (/api/my-schedule) под одним 'auth' (видит рядовой охранник).
 * БД baza_test.
 */
class ShiftScheduleApiTest extends TestCase
{
    use RefreshDatabase;

    private const FESTIVAL = '9d679bcf-b438-4ddb-ac04-023fa9bff4b8';

    private const SCHEDULES = '/api/schedules';

    private const MY = '/api/my-schedule';

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $this->seed(ChangesTestDataSeeder::class);     // admin id=1
        $this->seed(BazaRolePermissionsSeeder::class); // shift_chief: compose; ticketer/guard — нет compose
    }

    private function userWithRole(string $role): User
    {
        $u = User::factory()->create();
        DB::table('users')->where('id', $u->id)->update(['role' => $role, 'is_admin' => false]);

        return User::find($u->id);
    }

    private function repo(): ShiftScheduleRepositoryInterface
    {
        return app(ShiftScheduleRepositoryInterface::class);
    }

    /**
     * Создать плановую смену напрямую через репозиторий (для подготовки данных).
     *
     * @param  array<int, array{userId:int, role:string}>  $members
     */
    private function makePlan(int $chiefId, array $members, string $date, string $status = 'planned'): int
    {
        return $this->repo()->create(new ShiftScheduleDto(
            id: null,
            festivalId: self::FESTIVAL,
            kppPoint: 'main',
            shiftDate: Carbon::parse($date),
            plannedStart: Carbon::parse($date.' 08:00:00'),
            plannedEnd: Carbon::parse($date.' 18:00:00'),
            name: 'Утро',
            status: $status,
            chiefId: $chiefId,
            members: $members,
        ));
    }

    // ─── Доступ ────────────────────────────────────────────────────────────────

    public function test_schedules_requires_authentication(): void
    {
        $this->getJson(self::SCHEDULES)->assertUnauthorized();
    }

    public function test_schedules_ticketer_forbidden(): void
    {
        // У билетёра нет права shift.compose.
        $this->actingAs($this->userWithRole(ShiftRole::TICKETER))
            ->getJson(self::SCHEDULES)
            ->assertStatus(403);
    }

    public function test_my_schedule_available_to_guard_without_compose(): void
    {
        // Ключевой инвариант PR-A: охранник БЕЗ shift.compose видит СВОЁ расписание.
        $guard = $this->userWithRole(ShiftRole::GUARD);

        $this->actingAs($guard)
            ->getJson(self::MY)
            ->assertOk()
            ->assertJson(['success' => true]);
    }

    public function test_my_schedule_requires_authentication(): void
    {
        $this->getJson(self::MY)->assertUnauthorized();
    }

    // ─── Создание / список ───────────────────────────────────────────────────────

    public function test_admin_creates_schedule(): void
    {
        $chief = $this->userWithRole(ShiftRole::SHIFT_CHIEF);
        $member = $this->userWithRole(ShiftRole::TICKETER);

        $this->actingAs(User::find(1))->postJson(self::SCHEDULES, [
            'shift_date' => Carbon::now()->addDay()->toDateString(),
            'planned_start' => Carbon::now()->addDay()->setTime(8, 0)->toDateTimeString(),
            'planned_end' => Carbon::now()->addDay()->setTime(18, 0)->toDateTimeString(),
            'kpp_point' => 'main',
            'name' => 'Утро',
            'chief_id' => $chief->id,
            'members' => [
                ['user_id' => $member->id, 'role' => ShiftRole::TICKETER],
            ],
        ])->assertOk()->assertJson(['success' => true]);

        $list = $this->repo()->listForFestival(self::FESTIVAL);
        self::assertCount(1, $list);
        self::assertSame($chief->id, $list[0]['chief_id']);
        // Начальник добавлен в состав сверх переданного билетёра.
        self::assertSame(2, $list[0]['members_count']);
    }

    public function test_admin_create_requires_chief(): void
    {
        $member = $this->userWithRole(ShiftRole::TICKETER);

        $this->actingAs(User::find(1))->postJson(self::SCHEDULES, [
            'shift_date' => Carbon::now()->addDay()->toDateString(),
            'planned_start' => Carbon::now()->addDay()->setTime(8, 0)->toDateTimeString(),
            'members' => [['user_id' => $member->id]],
        ])->assertStatus(422);
    }

    public function test_lists_schedules_for_festival(): void
    {
        $chief = $this->userWithRole(ShiftRole::SHIFT_CHIEF);
        $this->makePlan($chief->id, [['userId' => $chief->id, 'role' => ShiftRole::SHIFT_CHIEF]], Carbon::now()->addDay()->toDateString());
        $this->makePlan($chief->id, [['userId' => $chief->id, 'role' => ShiftRole::SHIFT_CHIEF]], Carbon::now()->addDays(2)->toDateString());

        $res = $this->actingAs(User::find(1))->getJson(self::SCHEDULES)->assertOk();
        self::assertCount(2, $res->json('schedules'));
    }

    // ─── Личное расписание: изоляция ─────────────────────────────────────────────

    public function test_my_schedule_isolation_member_sees_non_member_does_not(): void
    {
        $chief = $this->userWithRole(ShiftRole::SHIFT_CHIEF);
        $member = $this->userWithRole(ShiftRole::GUARD);
        $stranger = $this->userWithRole(ShiftRole::GUARD);

        $this->makePlan(
            $chief->id,
            [
                ['userId' => $chief->id, 'role' => ShiftRole::SHIFT_CHIEF],
                ['userId' => $member->id, 'role' => ShiftRole::GUARD],
            ],
            Carbon::now()->addDay()->toDateString()
        );

        // Участник видит свою смену с собственной ролью.
        $resMember = $this->actingAs($member)->getJson(self::MY)->assertOk();
        $mine = $resMember->json('schedules');
        self::assertCount(1, $mine, 'участник видит свою плановую смену');
        self::assertSame(ShiftRole::GUARD, $mine[0]['my_role']);
        self::assertSame($chief->name, $mine[0]['chief_name']);

        // Посторонний не видит чужую смену.
        $resStranger = $this->actingAs($stranger)->getJson(self::MY)->assertOk();
        self::assertCount(0, $resStranger->json('schedules'), 'не-участник не видит чужую смену');
    }

    public function test_my_schedule_hides_cancelled_and_past(): void
    {
        $member = $this->userWithRole(ShiftRole::GUARD);

        // 1) Отменённая будущая — не показываем.
        $this->makePlan($member->id, [['userId' => $member->id, 'role' => ShiftRole::GUARD]], Carbon::now()->addDay()->toDateString(), 'cancelled');
        // 2) Прошедшая активная — не показываем.
        $this->makePlan($member->id, [['userId' => $member->id, 'role' => ShiftRole::GUARD]], Carbon::now()->subDay()->toDateString());
        // 3) Будущая активная — показываем.
        $futureId = $this->makePlan($member->id, [['userId' => $member->id, 'role' => ShiftRole::GUARD]], Carbon::now()->addDays(3)->toDateString());

        $res = $this->actingAs($member)->getJson(self::MY)->assertOk();
        $schedules = $res->json('schedules');
        self::assertCount(1, $schedules, 'только будущая активная смена');
        self::assertSame($futureId, $schedules[0]['id']);
    }

    // ─── Отмена / изоляция управления ────────────────────────────────────────────

    public function test_cancel_marks_cancelled(): void
    {
        $chief = $this->userWithRole(ShiftRole::SHIFT_CHIEF);
        $id = $this->makePlan($chief->id, [['userId' => $chief->id, 'role' => ShiftRole::SHIFT_CHIEF]], Carbon::now()->addDay()->toDateString());

        $this->actingAs(User::find(1))
            ->postJson(self::SCHEDULES.'/'.$id.'/cancel')
            ->assertOk()
            ->assertJson(['success' => true]);

        self::assertSame('cancelled', $this->repo()->find($id)['status']);
    }

    public function test_chief_cannot_cancel_foreign_schedule(): void
    {
        $a = $this->userWithRole(ShiftRole::SHIFT_CHIEF);
        $b = $this->userWithRole(ShiftRole::SHIFT_CHIEF);
        $foreignId = $this->makePlan($b->id, [['userId' => $b->id, 'role' => ShiftRole::SHIFT_CHIEF]], Carbon::now()->addDay()->toDateString());

        $this->actingAs($a)
            ->postJson(self::SCHEDULES.'/'.$foreignId.'/cancel')
            ->assertStatus(403);
    }

    public function test_cancel_missing_schedule_returns_404(): void
    {
        $this->actingAs(User::find(1))
            ->postJson(self::SCHEDULES.'/999999/cancel')
            ->assertStatus(404);
    }

    public function test_cancel_already_cancelled_returns_422(): void
    {
        $chief = $this->userWithRole(ShiftRole::SHIFT_CHIEF);
        $id = $this->makePlan(
            $chief->id,
            [['userId' => $chief->id, 'role' => ShiftRole::SHIFT_CHIEF]],
            Carbon::now()->addDay()->toDateString(),
            'cancelled'
        );

        $this->actingAs(User::find(1))
            ->postJson(self::SCHEDULES.'/'.$id.'/cancel')
            ->assertStatus(422);
    }

    public function test_update_cancelled_schedule_returns_422(): void
    {
        $chief = $this->userWithRole(ShiftRole::SHIFT_CHIEF);
        $id = $this->makePlan(
            $chief->id,
            [['userId' => $chief->id, 'role' => ShiftRole::SHIFT_CHIEF]],
            Carbon::now()->addDay()->toDateString(),
            'cancelled'
        );

        $this->actingAs(User::find(1))->putJson(self::SCHEDULES.'/'.$id, [
            'shift_date' => Carbon::now()->addDay()->toDateString(),
            'planned_start' => Carbon::now()->addDay()->setTime(8, 0)->toDateTimeString(),
            'chief_id' => $chief->id,
            'members' => [['user_id' => $chief->id, 'role' => ShiftRole::SHIFT_CHIEF]],
        ])->assertStatus(422);
    }
}
