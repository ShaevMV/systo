<?php

declare(strict_types=1);

namespace Baza\Tickets\Repositories;

use App\Models\FriendlyTicketModel;
use Baza\Tickets\Responses\FriendlyTicketResponse;
use Baza\Tickets\Responses\SpisokTicketResponse;
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

    public function find(string $q): array
    {
        $resultRawList = $this->friendlyTicketModel::whereKilter((int)$q)
            ->orWhere('project', 'like', '%' . $q . '%')
            ->orWhere('name', 'like', '%' . $q . '%')
            ->orWhere('comment', 'like', '%' . $q . '%')
            ->orWhere('email', 'like', '%' . $q . '%')
            ->orWhere('seller', 'like', '%' . $q . '%')
            ->get()->toArray();

        $result = [];
        foreach ($resultRawList as $item) {
            $result[] = FriendlyTicketResponse::fromState($item);
        }

        return $result;
    }
}
