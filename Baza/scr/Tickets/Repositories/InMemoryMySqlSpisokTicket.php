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

    private const UUID_FESTIVAL = '9d679bcf-b438-4ddb-ac04-023fa9bff4b4';

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
        $rawData = $this->spisokTicketModel::whereKilter($id)->first();

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
        $resultRawList = $this->spisokTicketModel::whereFestivalId(self::UUID_FESTIVAL)
            ->where(function ($query) use ($q) {
                return $query->orWhere('curator','like','%'.$q.'%')
                    ->orWhere('project','like','%'.$q.'%')
                    ->orWhere('name','like','%'.$q.'%')
                    ->orWhere('comment','like','%'.$q.'%')
                    ->orWhere('email','like','%'.$q.'%');
            })
            ->andWhere('festival_id', '=', self::UUID_FESTIVAL)
            ->get()->toArray();
        $result = [];
        foreach ($resultRawList as $item) {
            $result[]= SpisokTicketResponse::fromState($item, $q);
        }

        return $result;
    }
}
