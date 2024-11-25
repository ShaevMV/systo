<?php

namespace Baza\Tickets\Repositories;

use App\Models\AutoModel;
use Baza\Tickets\Responses\AutoTicketResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

class InMemoryMySqlAutoTicket implements AutoTicketRepositoryInterface
{
    private const UUID_FESTIVAL = '9d679bcf-b438-4ddb-ac04-023fa9bff4b5';

    public function __construct(
        private AutoModel $model
    )
    {
    }

    /**
     * @throws Throwable
     */
    public function skip(int $id, int $userId): bool
    {
        $rawData = $this->model::whereId($id)->first();

        DB::beginTransaction();
        try {
            $rawData->change_id = $userId;
            $rawData->date_change = Carbon::now();
            $rawData->save();
            DB::commit();

            return true;
        } catch (Throwable $throwable) {
            DB::rollBack();
            throw $throwable;
        }
    }

    public function find(string $q): array
    {
        $resultRawList = $this->model::where('auto', '<>', '')
            ->where('festival_id', '=', self::UUID_FESTIVAL)
            ->where(function ($query) use ($q) {
                return $query->where('auto', 'like', '%' . (int)$q . '%')
                    ->orWhereRaw('LOWER(`project`) LIKE ? ',['%'.strtolower(trim($q)).'%'])
                    ->orWhereRaw('LOWER(`curator`) LIKE ? ',['%'.strtolower(trim($q)).'%'])
                    ->orWhereRaw('LOWER(`comment`) LIKE ? ',['%'.strtolower(trim($q)).'%']);
            })
            ->get()->toArray();

        $result = [];
        foreach ($resultRawList as $item) {
            $result[] = AutoTicketResponse::fromState($item, $q);
        }

        return $result;
    }
}
