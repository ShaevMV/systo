<?php

namespace Baza\Tickets\Repositories;

use App\Models\ElTicketsModel;
use Baza\Shared\Domain\ValueObject\Uuid;
use Baza\Tickets\Responses\ElTicketResponse;
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

        if (is_null($data)) {
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

    public function find(string $q): array
    {
        $resultRawList = $this->elTicketsModel->whereKilter((int)$q)
            ->orWhere('name', 'like', '%' . $q . '%')
            ->orWhere('email', 'like', '%' . $q . '%')
            ->orWhere('phone', 'like', '%' . $q . '%')
            ->orWhere('comment', 'like', '%' . $q . '%')
            ->get()
            ->toArray();
        $result = [];
        foreach ($resultRawList as $item) {
            $result[] = ElTicketResponse::fromState($item);
        }

        return $result;
    }
}
