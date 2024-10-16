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
    private const UUID_FESTIVAL = null;

    public function __construct(
        private ElTicketsModel $elTicketsModel,
        private ?string $festivalId = self::UUID_FESTIVAL,
    )
    {
    }
    private function addFestivalUuid(): ElTicketsModel
    {
        if($this->festivalId) {
            return $this->elTicketsModel->where('festival_id', '=', self::UUID_FESTIVAL);
        }

        return $this->elTicketsModel;
    }


    public function search(Uuid $id): ?ElTicketResponse
    {
        $data = $this->addFestivalUuid()
            ->whereUuid($id->value())
            ->first()?->toArray();

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
        $rawData = $this->addFestivalUuid()->whereKilter($id)
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
        $resultRawList = $this->addFestivalUuid()
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
