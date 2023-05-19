<?php

declare(strict_types=1);

namespace Baza\Changes\Repositories;

use App\Models\ChangesModel;
use Baza\Changes\Applications\Report\ReportForChangesDto;
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


    public function getAllReport(): array
    {
        $resultRaw = DB::select("select `changes`.`id`,
       GROUP_CONCAT(u.name SEPARATOR ',') AS user_name,
       `changes`.`count_live_tickets`,
       `changes`.`count_el_tickets`,
       `changes`.`count_drug_tickets`,
       `changes`.`count_spisok_tickets`,
       `changes`.`start`,
       `changes`.`end`
from `changes`
         left join `users` as `u` on JSON_CONTAINS(changes.user_id, CAST(u.id as JSON), '$')
group by `changes`.`id`");

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

    public function updateOrCreate(array $userList, Carbon $start, ?int $id = null): bool
    {
        if (!is_null($id)) {
            $model = $this->model::find($id);
        } else {
            $model = $this->model;
        }
        $model->user_id = Json::encode($userList);
        $model->start = $start;

        return $model->save();
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
        return $this->model::find($id)->delete($id);
    }
}
