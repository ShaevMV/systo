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

        return $query->get()->map(function (ShiftScheduleModel $s): array {
            $chiefName = $s->chief_id !== null
                ? User::whereKey($s->chief_id)->value('name')
                : null;

            return [
                'id' => (int) $s->id,
                'festival_id' => (string) $s->festival_id,
                'kpp_point' => $s->kpp_point,
                'shift_date' => $s->shift_date?->toDateString(),
                'planned_start' => $s->planned_start?->toDateTimeString(),
                'planned_end' => $s->planned_end?->toDateTimeString(),
                'name' => $s->name,
                'status' => (string) $s->status,
                'chief_id' => $s->chief_id !== null ? (int) $s->chief_id : null,
                'chief_name' => $chiefName,
                'members_count' => ShiftScheduleUserModel::where('schedule_id', $s->id)->count(),
            ];
        })->all();
    }

    public function getMySchedule(int $userId): array
    {
        // Плановые смены этого сотрудника (состоит в составе).
        $scheduleIds = ShiftScheduleUserModel::where('user_id', $userId)->pluck('schedule_id');

        if ($scheduleIds->isEmpty()) {
            return [];
        }

        $today = Carbon::now()->toDateString();

        $query = $this->model::query()
            ->whereIn('id', $scheduleIds)
            ->where('status', '!=', 'cancelled')
            ->whereDate('shift_date', '>=', $today)
            ->orderBy('shift_date')
            ->orderBy('planned_start');

        return $query->get()->map(function (ShiftScheduleModel $s) use ($userId): array {
            $chiefName = $s->chief_id !== null
                ? User::whereKey($s->chief_id)->value('name')
                : null;

            $myRole = ShiftScheduleUserModel::where('schedule_id', $s->id)
                ->where('user_id', $userId)
                ->value('role');

            return [
                'id' => (int) $s->id,
                'date' => $s->shift_date?->toDateString(),
                'start' => $s->planned_start?->toDateTimeString(),
                'end' => $s->planned_end?->toDateTimeString(),
                'kpp_point' => $s->kpp_point,
                'name' => $s->name,
                'status' => (string) $s->status,
                'chief_name' => $chiefName,
                'my_role' => $myRole !== null ? (string) $myRole : null,
                'members_count' => ShiftScheduleUserModel::where('schedule_id', $s->id)->count(),
            ];
        })->all();
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

        // Карта userId → роль из DTO (дедуп: последняя роль для повтора).
        $roleByUser = [];
        foreach ($dto->members as $member) {
            $userId = (int) ($member['userId'] ?? 0);
            if ($userId <= 0) {
                continue;
            }
            $role = (string) ($member['role'] ?? '');
            $roleByUser[$userId] = ShiftRole::isValid($role) ? $role : ShiftRole::GUARD;
        }

        // Начальник смены обязан входить в состав с ролью shift_chief (инвариант).
        if ($dto->chiefId !== null) {
            $roleByUser[$dto->chiefId] = ShiftRole::SHIFT_CHIEF;
        }

        foreach ($roleByUser as $userId => $role) {
            ShiftScheduleUserModel::create([
                'schedule_id' => $scheduleId,
                'user_id' => $userId,
                'role' => $role,
            ]);
        }
    }
}
