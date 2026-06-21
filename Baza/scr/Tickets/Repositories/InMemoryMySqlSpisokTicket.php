<?php

declare(strict_types=1);

namespace Baza\Tickets\Repositories;

use App\Models\SpisokTicketModel;
use Baza\Tickets\Responses\SpisokTicketResponse;
use Carbon\Carbon;
use DB;
use Shared\Domain\ValueObject\Uuid;
use Throwable;

class InMemoryMySqlSpisokTicket implements SpisokTicketsRepositoryInterface
{

    private const UUID_FESTIVAL = '9d679bcf-b438-4ddb-ac04-023fa9bff4b8';

    public function __construct(
        private SpisokTicketModel $spisokTicketModel
    )
    {
    }


    public function search(Uuid $kilter): ?SpisokTicketResponse
    {
        $data = $this->spisokTicketModel::where('ticket_uuid','=',$kilter)
            ->where('festival_id', '=', self::UUID_FESTIVAL)
            ->first()?->toArray();

        if (is_null($data)) {
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

        // Серверная защита от повторного впуска (см. InMemoryMySqlElTicket::skip).
        if ($rawData === null) {
            throw new \DomainException('Билет не найден в Базе входа');
        }
        if ($rawData->date_change !== null) {
            throw new \DomainException('Билет уже был пропущен ' . $rawData->date_change);
        }

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
        $q = trim($q);
        if ($q === '') {
            return [];
        }
        $like = '%' . strtolower($q) . '%';

        $resultRawList = $this->spisokTicketModel
            ->where('festival_id', '=', self::UUID_FESTIVAL)
            ->where(function ($query) use ($like) {
                // comment убран из гостевого поиска (внутренние заметки персонала).
                $query->orWhereRaw('LOWER(`name`) LIKE ? ', [$like])
                    ->orWhereRaw('LOWER(`curator`) LIKE ? ', [$like])
                    ->orWhereRaw('LOWER(`project`) LIKE ? ', [$like])
                    ->orWhereRaw('LOWER(`email`) LIKE ? ', [$like]);
            })
            ->get()
            ->toArray();

        $result = [];
        foreach ($resultRawList as $item) {
            $result[] = SpisokTicketResponse::fromState($item, $q);
        }

        return $result;
    }
}
