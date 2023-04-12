<?php

namespace Baza\Tickets\Repositories;

use App\Models\ElTicketsModel;
use Baza\Shared\Domain\ValueObject\Uuid;
use Baza\Tickets\Applications\Search\ElTicket\ElTicketResponse;
use Carbon\Carbon;
use DB;
use Throwable;

class InMemoryMySqlElTicket implements ElTicketsRepositoryInterface
{

    public function __construct(
        private ElTicketsModel $elTicketsModel
    )
    {
    }

    public function search(Uuid $id): ?ElTicketResponse
    {
        $data = $this->elTicketsModel::whereUuid($id->value())->first()?->toArray();

        if(is_null($data)) {
            return null;
        }


        return ElTicketResponse::fromState($data);
    }

    /**
     * @throws Throwable
     */
    public function skip(int $id, int $userId): bool
    {
        $rawData = $this->elTicketsModel::whereKilter($id)->first();

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
