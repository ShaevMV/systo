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
    private const UUID_FESTIVAL = '9d679bcf-b438-4ddb-ac04-023fa9bff4b4';

    public function __construct(
        private ElTicketsModel $elTicketsModel
    )
    {
    }

    public function search(Uuid $id): ?ElTicketResponse
    {
        $data = $this->elTicketsModel::whereFestivalId(self::UUID_FESTIVAL)
            ->whereUuid($id->value())->first()?->toArray();

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
        $resultRawList = $this->elTicketsModel::whereFestivalId(self::UUID_FESTIVAL)
            ->where(function($query) use ($q) {
                return $query->whereKilter((int)$q)
                    ->orWhereRaw('LOWER(`name`) LIKE ? ',['%'.strtolower(trim($q)).'%'])
                    ->orWhereRaw('LOWER(`comment`) LIKE ? ',['%'.strtolower(trim($q)).'%'])
                    ->orWhereRaw('LOWER(`email`) LIKE ? ',['%'.strtolower(trim($q)).'%'])
                    ->orWhereRaw('LOWER(`phone`) LIKE ? ',['%'.strtolower(trim($q)).'%']);
            })
            ->get()->toArray();

        $result = [];
        foreach ($resultRawList as $item) {
            $result[] = ElTicketResponse::fromState($item, $q);
        }

        return $result;
    }
}
