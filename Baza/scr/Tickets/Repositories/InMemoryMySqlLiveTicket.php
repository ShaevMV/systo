<?php

declare(strict_types=1);

namespace Baza\Tickets\Repositories;

use App\Models\LiveTicketModel;
use Baza\Tickets\Applications\Search\LiveTicket\LiveTicketResponse;
use Carbon\Carbon;
use DB;
use Throwable;

class InMemoryMySqlLiveTicket implements LiveTicketRepositoryInterface
{

    public function __construct(
        private LiveTicketModel $liveTicketModel
    )
    {
    }


    public function search(int $kilter): ?LiveTicketResponse
    {
        $data = $this->liveTicketModel::whereKilter($kilter)->first()?->toArray();

        if(is_null($data)) {
            return null;
        }

        return LiveTicketResponse::fromState($data);
    }

    /**
     * @throws Throwable
     */
    public function skip(int $id, int $userId): bool
    {
        $rawData = $this->liveTicketModel::whereKilter($id)->first();

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
}
