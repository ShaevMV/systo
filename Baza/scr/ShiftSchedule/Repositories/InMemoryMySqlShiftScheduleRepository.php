<?php

declare(strict_types=1);

namespace Baza\ShiftSchedule\Repositories;

use App\Models\ShiftScheduleModel;
use App\Models\ShiftScheduleUserModel;
use App\Models\User;
use Baza\Shared\Domain\ValueObject\ShiftRole;
use Baza\ShiftSchedule\Dto\ShiftScheduleDto;
use Carbon\Carbon;
use DB;

/**
 * Плановое расписание смен (PR-A). БД — ТОЛЬКО здесь (Dependency Rule).
 *
 * Стиль скопирован с InMemoryMySqlChangesRepository: транзакция + дедуп состава +
 * пересборка состава (delete старые → insert текущие, нет FK/каскада).
 */
class InMemoryMySqlShiftScheduleRepository implements ShiftScheduleRepositoryInterface
{
    public function __construct(
        private ShiftScheduleModel $model,
    ) {
    }

    public function create(ShiftScheduleDto $dto): int
    {
        return DB::transaction(function () use ($dto): int {
            $model = $this->model->newInstance();
            $this->fillFromDto($model, $dto);
            $model->save();

            $this->syncMembers((int) $model->id, $dto);

            return (int) $model->id;
        });
    }

    public function edit(int $id, ShiftScheduleDto $dto): bool
    {
        return DB::transaction(function () use ($id, $dto): bool {
            $model = $this->model::find($id);
            if ($model === null) {
                return false;
            }

            $this->fillFromDto($model, $dto);
            if (! $model->save()) {
                return false;
            }

            $this->syncMembers($id, $dto);

            return true;
        });
    }

    public function cancel(int $id): bool
    {
        $model = $this->model::find($id);
        if ($model === null) {
            return false;
        }

        $model->status = 'cancelled';

        return (bool) $model->save();
    }

    public function exists(int $id): bool
    {
        return $this->model::whereKey($id)->exists();
    }

    public function find(int $id): ?array
    {
        /** @var ShiftScheduleModel|null $model */
        $model = $this->model::find($id);
        if ($model === null) {
            return null;
        }

        $members = ShiftScheduleUserModel::where('schedule_id', $id)
            ->get(['user_id', 'role'])
            ->map(static fn (ShiftScheduleUserModel $m): array => [
                'user_id' => (int) $m->user_id,
                'role' => (string) $m->role,
            ])
            ->all();

        return [
            'id' => (int) $model->id,
            'festival_id' => (string) $model->festival_id,
            'kpp_point' => $model->kpp_point,
            'shift_date' => $model->shift_date?->toDateString(),
            'planned_start' => $model->planned_start?->toDateTimeString(),
            'planned_end' => $model->planned_end?->toDateTimeString(),
            'name' => $model->name,
            'status' => (string) $model->status,
            'chief_id' => $model->chief_id !== null ? (int) $model->chief_id : null,
            'members' => $members,
        ];
    }

    public function listForFestival(string $festivalId, ?string $shiftDate = null, ?string $kppPoint = null): array
    {
        $query = $this->model::query()
            ->where('festival_id', $festivalId)
            ->orderBy('shift_date')
            ->orderBy('planned_start');

        if (! empty($shiftDate)) {
            $query->whereDate('shift_date', $shiftDate);
        }
        if (! empty($kppPoint)) {
            $query->where('kpp_point', $kppPoint);
        }

        $schedules = $query->get();
        if ($schedules->isEmpty()) {
            return [];
        }

        // Батч-преднагрузка (без N+1): имена начальников + счётчики состава одним запросом каждый.
        $chiefNames = $this->chiefNamesFor($schedules);
        $counts = $this->memberCountsFor($schedules->pluck('id')->all());

        return $schedules->map(fn (ShiftScheduleModel $s): array => [
            'id' => (int) $s->id,
            'festival_id' => (string) $s->festival_id,
            'kpp_point' => $s->kpp_point,
            'shift_date' => $s->shift_date?->toDateString(),
            'planned_start' => $s->planned_start?->toDateTimeString(),
            'planned_end' => $s->planned_end?->toDateTimeString(),
            'name' => $s->name,
            'status' => (string) $s->status,
            'chief_id' => $s->chief_id !== null ? (int) $s->chief_id : null,
            'chief_name' => $s->chief_id !== null ? ($chiefNames[(int) $s->chief_id] ?? null) : null,
            'members_count' => (int) ($counts[(int) $s->id] ?? 0),
        ])->all();
    }

    public function getMySchedule(int $userId): array
    {
        // Плановые смены этого сотрудника (состоит в составе).
        $scheduleIds = ShiftScheduleUserModel::where('user_id', $userId)->pluck('schedule_id');

        if ($scheduleIds->isEmpty()) {
            return [];
        }

        $today = Carbon::now()->toDateString();

        $schedules = $this->model::query()
            ->whereIn('id', $scheduleIds)
            ->where('status', '!=', 'cancelled')
            ->whereDate('shift_date', '>=', $today)
            ->orderBy('shift_date')
            ->orderBy('planned_start')
            ->get();

        if ($schedules->isEmpty()) {
            return [];
        }

        // Батч-преднагрузка (без N+1): имена начальников, счётчики и МОЯ роль по сменам.
        $ids = $schedules->pluck('id')->all();
        $chiefNames = $this->chiefNamesFor($schedules);
        $counts = $this->memberCountsFor($ids);
        $myRoles = ShiftScheduleUserModel::whereIn('schedule_id', $ids)
            ->where('user_id', $userId)
            ->pluck('role', 'schedule_id');

        return $schedules->map(fn (ShiftScheduleModel $s): array => [
            'id' => (int) $s->id,
            'date' => $s->shift_date?->toDateString(),
            'start' => $s->planned_start?->toDateTimeString(),
            'end' => $s->planned_end?->toDateTimeString(),
            'kpp_point' => $s->kpp_point,
            'name' => $s->name,
            'status' => (string) $s->status,
            'chief_name' => $s->chief_id !== null ? ($chiefNames[(int) $s->chief_id] ?? null) : null,
            'my_role' => isset($myRoles[$s->id]) ? (string) $myRoles[$s->id] : null,
            'members_count' => (int) ($counts[(int) $s->id] ?? 0),
        ])->all();
    }

    /**
     * Имена начальников пачкой [user_id => name] — против N+1 в списках.
     *
     * @param  \Illuminate\Support\Collection<int, ShiftScheduleModel>  $schedules
     * @return array<int, string>
     */
    private function chiefNamesFor($schedules): array
    {
        $chiefIds = $schedules->pluck('chief_id')->filter()->unique()->values()->all();
        if ($chiefIds === []) {
            return [];
        }

        return User::whereIn('id', $chiefIds)->pluck('name', 'id')->all();
    }

    /**
     * Счётчики состава пачкой [schedule_id => count] — против N+1.
     *
     * @param  array<int, int>  $scheduleIds
     * @return array<int, int>
     */
    private function memberCountsFor(array $scheduleIds): array
    {
        if ($scheduleIds === []) {
            return [];
        }

        return ShiftScheduleUserModel::whereIn('schedule_id', $scheduleIds)
            ->select('schedule_id', DB::raw('COUNT(*) as c'))
            ->groupBy('schedule_id')
            ->pluck('c', 'schedule_id')
            ->map(static fn ($c): int => (int) $c)
            ->all();
    }

    private function fillFromDto(ShiftScheduleModel $model, ShiftScheduleDto $dto): void
    {
        $model->festival_id = $dto->festivalId;
        $model->kpp_point = $dto->kppPoint;
        $model->shift_date = $dto->shiftDate;
        $model->planned_start = $dto->plannedStart;
        $model->planned_end = $dto->plannedEnd;
        $model->name = $dto->name;
        $model->status = $dto->status;
        $model->chief_id = $dto->chiefId;
    }

    /**
     * Пересобирает состав плана (delete старые → insert текущие) — нет FK/каскада,
     * как syncChangeUsers в Changes. Дедуп по user_id (UNIQUE(schedule_id,user_id)).
     * Начальник (chiefId) гарантированно получает роль shift_chief и входит в состав.
     */
    private function syncMembers(int $scheduleId, ShiftScheduleDto $dto): void
    {
        ShiftScheduleUserModel::where('schedule_id', $scheduleId)->delete();

        // Карта userId → роль из DTO: явная валидная роль или null (требует мягкого маппинга).
        $roleByUser = [];
        foreach ($dto->members as $member) {
            $userId = (int) ($member['userId'] ?? 0);
            if ($userId <= 0) {
                continue;
            }
            $role = $member['role'] ?? null;
            $roleByUser[$userId] = (is_string($role) && ShiftRole::isValid($role)) ? $role : null;
        }

        // Начальник смены обязан входить в состав с ролью shift_chief (инвариант).
        if ($dto->chiefId !== null) {
            $roleByUser[(int) $dto->chiefId] = ShiftRole::SHIFT_CHIEF;
        }

        // Мягкий маппинг роли для участников без явной (по users.role/is_admin) — одним запросом.
        // БД-логика роли держится здесь (репозиторий), а не в контроллере (Dependency Rule).
        $needMap = array_keys(array_filter($roleByUser, static fn ($r): bool => $r === null));
        if ($needMap !== []) {
            $users = User::whereIn('id', $needMap)->get(['id', 'is_admin', 'role'])->keyBy('id');
            foreach ($needMap as $userId) {
                $u = $users->get($userId);
                $roleByUser[$userId] = ShiftRole::fromUser((bool) ($u?->is_admin ?? false), $u?->role);
            }
        }

        foreach ($roleByUser as $userId => $role) {
            ShiftScheduleUserModel::create([
                'schedule_id' => $scheduleId,
                'user_id' => (int) $userId,
                'role' => $role,
            ]);
        }
    }
}
