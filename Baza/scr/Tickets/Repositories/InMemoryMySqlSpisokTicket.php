<?php

declare(strict_types=1);

namespace Baza\Tickets\Repositories;

use App\Models\SpisokTicketModel;
use Baza\Tickets\Responses\SpisokTicketResponse;
use Carbon\Carbon;
use DB;
use Throwable;

class InMemoryMySqlSpisokTicket implements SpisokTicketsRepositoryInterface
{

    private const UUID_FESTIVAL = '9d679bcf-b438-4ddb-ac04-023fa9bff4b5';

    public function __construct(
        private SpisokTicketModel $spisokTicketModel
    )
    {
    }


    public function search(int $kilter): ?SpisokTicketResponse
    {
        $data = $this->spisokTicketModel::whereKilter($kilter)
            ->where('festival_id', '=', self::UUID_FESTIVAL)
            ->first()?->toArray();

        if(is_null($data)) {
            return null;
        }

        return SpisokTicketResponse::fromState($data);
    }

    /**
     * @throws Throwable
     */
    public function skip(int $id, int $userId): bool
    {
        $rawData = $this->spisokTicketModel::whereKilter($id)
            ->where('festival_id', '=', self::UUID_FESTIVAL)
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
        $resultRawList = $this->spisokTicketModel
            ->where('festival_id', '=', self::UUID_FESTIVAL)
            ->where(function ($query) use ($q) {
                return $query->orWhereRaw('LOWER(`curator`) LIKE ? ',['%'.strtolower(trim($q)).'%'])
                    ->orWhereRaw('LOWER(`project`) LIKE ? ',['%'.strtolower(trim($q)).'%'])
                    ->orWhereRaw('LOWER(`name`) LIKE ? ',['%'.strtolower(trim($q)).'%'])
                    ->orWhereRaw('LOWER(`comment`) LIKE ? ',['%'.strtolower(trim($q)).'%'])
                    ->orWhereRaw('LOWER(`email`) LIKE ? ',['%'.strtolower(trim($q)).'%']);
            })
            ->get()
            ->toArray();

        $result = [];
        foreach ($resultRawList as $item) {
            $result[]= SpisokTicketResponse::fromState($item, $q);
        }

        return $result;
    }
}
