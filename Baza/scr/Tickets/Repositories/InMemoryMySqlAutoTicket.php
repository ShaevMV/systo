<?php

namespace Baza\Tickets\Repositories;

use App\Models\AutoModel;
use Baza\Tickets\Responses\AutoTicketResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

class InMemoryMySqlAutoTicket implements AutoTicketRepositoryInterface
{
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
        $resultRawList = $this->model::where('auto','<>','')
            ->where(function($query) use ($q) {
                return $query->where('auto', 'like', '%' . (int)$q . '%')
                    ->orWhere('project', 'like', '%' . $q . '%')
                    ->orWhere('curator', 'like', '%' . $q . '%')
                    ->orWhere('comment', 'like', '%' . $q . '%');
            })->get()->toArray();

        $result = [];
        foreach ($resultRawList as $item) {
            $result[]= AutoTicketResponse::fromState($item, $q);
        }

        return $result;
    }
}
