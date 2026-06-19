<?php

declare(strict_types=1);

namespace Baza\Changes\Repositories;

use App\Models\ChangesModel;
use App\Models\ChangeUserModel;
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

    public function updateOrCreate(array $userList, Carbon $start, string $festivalId, ?int $id = null): bool
    {
        return DB::transaction(function () use ($userList, $start, $festivalId, $id) {
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
            $this->syncChangeUsers((int) $model->id, $userList);

            return true;
        });
    }

    /**
     * Пересобирает состав change_user для смены (delete старые → insert текущие).
     * Роль участника — мягкий маппинг ShiftRole::fromUser: явная глобальная
     * users.role перекрывает производную по is_admin. Явное назначение ролей
     * именно в этой смене и выбор главного делается из UI в PR-6.
     *
     * @param int[] $userList
     */
    private function syncChangeUsers(int $changeId, array $userList): void
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

            ChangeUserModel::create([
                'change_id' => $changeId,
                'user_id'   => $userId,
                'role'      => ShiftRole::fromUser((bool) ($user?->is_admin ?? false), $user?->role),
            ]);
        }
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
}
