<?php

declare(strict_types=1);

namespace Baza\Tickets\Repositories;

use App\Models\FriendlyTicketModel;
use Baza\Tickets\Applications\Search\FriendlyTicket\FriendlyTicketResponse;
use Baza\Tickets\Applications\Search\SpisokTicket\SpisokTicketResponse;
use Carbon\Carbon;
use DB;
use Throwable;

class InMemoryMySqlFriendlyTicket implements FriendlyTicketRepositoryInterface
{

    public function __construct(
        private FriendlyTicketModel $friendlyTicketModel
    )
    {
    }


    public function search(int $kilter): ?FriendlyTicketResponse
    {
        $data = $this->friendlyTicketModel::whereKilter($kilter)->first()?->toArray();

        if(is_null($data)) {
            return null;
        }

        return FriendlyTicketResponse::fromState($data);
    }

    /**
     * @throws Throwable
     */
    public function skip(int $id, int $userId): bool
    {
        $rawData = $this->friendlyTicketModel::whereKilter($id)->first();

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
