<?php

declare(strict_types=1);

namespace Baza\Changes\Repositories;

use App\Models\ChangesModel;
use App\Models\ChangeUserModel;
use App\Models\FestivalModel;
use App\Models\User;
use Baza\Changes\Applications\Report\ReportForChangesDto;
use Baza\Shared\Domain\ValueObject\ShiftRole;
use Carbon\Carbon;
use DB;
use Nette\Utils\Json;
use Nette\Utils\JsonException;

class InMemoryMySqlChangesRepository implements ChangesRepositoryInterface
{
    public function __construct(
        private ChangesModel $model,
    )
    {
    }


    public function getAllReport(string $festivalId): array
    {
        $resultRaw = DB::select("select `changes`.`id`,
       GROUP_CONCAT(u.name SEPARATOR ',') AS user_name,
       `changes`.`count_live_tickets`,
       `changes`.`count_el_tickets`,
       `changes`.`count_drug_tickets`,
       `changes`.`count_spisok_tickets`,
       `changes`.`count_auto_tickets`,
       `changes`.`count_parking_tickets`,
       `changes`.`count_parking_free_tickets`,
       `changes`.`count_parking_cross-country_tickets`,
       `changes`.`start`,
       `changes`.`end`
from `changes`
         left join `users` as `u` on JSON_CONTAINS(changes.user_id, CAST(u.id as JSON), '$')
             WHERE `changes`.`festival_id` = :festivalId
group by `changes`.`id`", [
    'festivalId' => $festivalId
        ]);

        $result = [];
        foreach ($resultRaw as $item) {
            $result[] = ReportForChangesDto::fromState((array)$item);
        }

        return $result;
    }

    public function close(int $changeId): int
    {
        /** @var ChangesModel $result */
        $result = $this->model::find($changeId);
        $result->end = Carbon::now();
        $result->save();

        return $result->id;
    }

    public function addTicket(string $columName, int $changeId): bool
    {
        $result = $this->model::find($changeId);
        $result->increment($columName);

        return $result->save();
    }

    public function getChangeId(int $userId): ?int
    {
        $time = Carbon::now();
        $result = $this->model->whereJsonContains('user_id', $userId)
            ->where('end', '=', null)
            ->orderBy('start')
            ->first();

        return $result?->id;
    }

    public function updateOrCreate(array $userList, Carbon $start, string $festivalId, ?int $id = null, ?int $chiefId = null): bool
    {
        return DB::transaction(function () use ($userList, $start, $festivalId, $id, $chiefId) {
            // Дедуп состава: один человек в смене ровно один раз. Защита от кривого
            // POST compound[] с дублем — иначе UNIQUE(change_id,user_id) в change_user
            // уронил бы сохранение. JSON и change_user пишем одинаковым составом.
            $userList = array_values(array_unique(array_map('intval', $userList)));

            if (!is_null($id)) {
                $model = $this->model::find($id);
            } else {
                $model = $this->model;
            }
            $model->user_id = Json::encode($userList);
            $model->festival_id = $festivalId;
            $model->start = $start;

            if (!$model->save()) {
                return false;
            }

            // Двойная запись (Ф2): состав смены с ролями в change_user — параллельно
            // changes.user_id JSON выше. Читающие экраны пока используют JSON → вход цел.
            $this->syncChangeUsers((int) $model->id, $userList, $chiefId);

            return true;
        });
    }

    /**
     * Пересобирает состав change_user для смены (delete старые → insert текущие).
     * Роль участника: выбранный главный (chiefId) → shift_chief; остальные —
     * мягкий маппинг ShiftRole::fromUser (явная users.role перекрывает is_admin).
     *
     * @param int[] $userList
     */
    private function syncChangeUsers(int $changeId, array $userList, ?int $chiefId = null): void
    {
        ChangeUserModel::where('change_id', $changeId)->delete();

        if ($userList === []) {
            return;
        }

        $users = User::whereIn('id', array_map('intval', $userList))
            ->get(['id', 'is_admin', 'role'])
            ->keyBy('id');

        foreach ($userList as $userId) {
            $userId = (int) $userId;
            $user = $users->get($userId);

            $role = $userId === $chiefId
                ? ShiftRole::SHIFT_CHIEF
                : ShiftRole::fromUser((bool) ($user?->is_admin ?? false), $user?->role);

            ChangeUserModel::create([
                'change_id' => $changeId,
                'user_id'   => $userId,
                'role'      => $role,
            ]);
        }
    }

    public function getChiefId(int $changeId): ?int
    {
        $value = ChangeUserModel::where('change_id', $changeId)
            ->where('role', ShiftRole::SHIFT_CHIEF)
            ->value('user_id');

        return $value !== null ? (int) $value : null;
    }

    public function festivalIdForChange(int $changeId): ?string
    {
        $value = $this->model::whereKey($changeId)->value('festival_id');

        return ($value !== null && $value !== '') ? (string) $value : null;
    }

    /**
     * @throws JsonException
     */
    public function get(int $id): array
    {
        return $this->model::find($id)->toArray();
    }

    public function remove(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            // Нет FK/каскада → состав смены чистим явно, иначе осиротевшие строки (Ф2).
            ChangeUserModel::where('change_id', $id)->delete();

            $model = $this->model::find($id);

            return $model !== null && (bool) $model->delete();
        });
    }

    public function exists(int $id): bool
    {
        return $this->model::whereKey($id)->exists();
    }

    public function listOpen(?int $chiefId = null, ?string $festivalId = null): array
    {
        $query = $this->model::query()
            ->whereNull('end')
            ->orderByDesc('id');

        // Фильтр по фестивалю (TD-48) — опционален: null = все открытые смены (как было,
        // т.к. раньше все смены жили на одном зашитом фестивале).
        if ($festivalId !== null && $festivalId !== '') {
            $query->where('festival_id', $festivalId);
        }

        // Изоляция начальника: только смены, где он shift_chief в change_user.
        if ($chiefId !== null) {
            $changeIds = ChangeUserModel::where('user_id', $chiefId)
                ->where('role', ShiftRole::SHIFT_CHIEF)
                ->pluck('change_id');
            $query->whereIn('id', $changeIds);
        }

        $shifts = $query->get();
        if ($shifts->isEmpty()) {
            return [];
        }

        // Названия фестивалей пачкой (без N+1) — как chief_name через User.
        $festivalNames = FestivalModel::query()
            ->whereIn('id', $shifts->pluck('festival_id')->filter()->unique()->values()->all())
            ->pluck('name', 'id');

        return $shifts->map(function (ChangesModel $c) use ($festivalNames): array {
            $chiefUserId = $this->getChiefId((int) $c->id);

            return [
                'id' => (int) $c->id,
                'chief_id' => $chiefUserId,
                'chief_name' => $chiefUserId !== null ? User::whereKey($chiefUserId)->value('name') : null,
                'members_count' => ChangeUserModel::where('change_id', $c->id)->count(),
                'start' => $c->start ? (string) $c->start : null,
                'festival_id' => $c->festival_id !== null ? (string) $c->festival_id : null,
                'festival_name' => $c->festival_id !== null ? ($festivalNames[$c->festival_id] ?? null) : null,
                'counts' => [
                    'el' => (int) $c->count_el_tickets,
                    'live' => (int) $c->count_live_tickets,
                    'spisok' => (int) $c->count_spisok_tickets,
                    'drug' => (int) $c->count_drug_tickets,
                    'auto' => (int) $c->count_auto_tickets,
                ],
            ];
        })->all();
    }
}
