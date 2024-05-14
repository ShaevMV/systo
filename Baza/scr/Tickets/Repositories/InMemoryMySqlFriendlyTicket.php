<?php

declare(strict_types=1);

namespace Baza\Tickets\Repositories;

use App\Models\FriendlyTicketModel;
use Baza\Tickets\Responses\FriendlyTicketResponse;
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
        $data = $this->friendlyTicketModel::whereKilter($kilter)
            ->whereFestivalId(env('UUID_SECOND_FESTIVAL', '9d679bcf-b438-4ddb-ac04-023fa9bff4b4'))
            ->first()?->toArray();

        if (is_null($data)) {
            return null;
        }

        return FriendlyTicketResponse::fromState($data);
    }

    /**
     * @throws Throwable
     */
    public function skip(int $id, int $userId): bool
    {
        $rawData = $this->friendlyTicketModel::whereKilter($id)
            ->whereFestivalId(env('UUID_SECOND_FESTIVAL', '9d679bcf-b438-4ddb-ac04-023fa9bff4b4'))
            ->first();

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
        $resultRawList = $this->friendlyTicketModel::whereFestivalId(env('UUID_SECOND_FESTIVAL', '9d679bcf-b438-4ddb-ac04-023fa9bff4b4'))
                ->where(function($query) use ($q) {
                    return $query->whereKilter((int)$q)
                        ->orWhere('project', 'like', '%' . $q . '%')
                        ->orWhere('name', 'like', '%' . $q . '%')
                        ->orWhere('comment', 'like', '%' . $q . '%')
                        ->orWhere('email', 'like', '%' . $q . '%');
                })
            ->get()->toArray();

        $result = [];
        foreach ($resultRawList as $item) {
            $result[] = FriendlyTicketResponse::fromState($item,$q);
        }

        return $result;
    }
}
