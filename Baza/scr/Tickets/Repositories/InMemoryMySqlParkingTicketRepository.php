<?php

declare(strict_types=1);

namespace Baza\Tickets\Repositories;

use App\Models\ParkingTicketModel;
use Baza\Tickets\Responses\LiveTicketResponse;
use Baza\Tickets\Responses\ParkingTicketResponse;
use Carbon\Carbon;
use DB;
use Throwable;

class InMemoryMySqlParkingTicketRepository implements ParkingTicketRepositoryInterface
{
    public function __construct(
        private ParkingTicketModel $model
    )
    {
    }

    public function search(int $kilter, string $type): ?ParkingTicketResponse
    {
        $data = $this->model::whereKilter($kilter)
            ->whereType($type)
            ->first()
            ?->toArray();

        if (is_null($data)) {
            return null;
        }

        return ParkingTicketResponse::fromState($data);
    }

    /**
     * @throws Throwable
     */
    public function skip(int $id, int $userId): bool
    {
        $rawData = $this->model::whereKilter($id)
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

    /**
     * @throws Throwable
     */
    public function create(int $start, int $end, string $type): bool
    {
        DB::beginTransaction();
        try {
            for ($kilter = $start; $kilter < $end; $kilter++) {
                $this->model::create([
                    'kilter' => $kilter,
                    'type' => $type
                ]);
            }
            DB::commit();

            return true;
        } catch (Throwable $exception) {
            DB::rollBack();
            throw $exception;
        }
    }

    public function find(string $q, string $type): array
    {
        $resultRawList =  $this->model::whereKilter((int)$q)
            ->whereType($type)
            ->get()->toArray();

        $result = [];
        foreach ($resultRawList as $item) {
            $result[] = LiveTicketResponse::fromState($item);
        }

        return $result;
    }
}
