<?php

namespace Baza\Tickets\Repositories;

use App\Models\AutoModel;
use Baza\Tickets\Responses\AutoTicketResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

class InMemoryMySqlAutoTicket implements AutoTicketRepositoryInterface
{
    private const UUID_FESTIVAL = '9d679bcf-b438-4ddb-ac04-023fa9bff4b8';

    public function __construct(
        private AutoModel $model
    )
    {
    }

    /**
     * @throws Throwable
     */
    public function skip(int $id, int $userId): bool
    {
        $rawData = $this->model::whereId($id)->first();

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

        $resultRawList = $this->model::where('auto', '<>', '')
            ->where('festival_id', '=', self::UUID_FESTIVAL)
            ->where(function ($query) use ($like) {
                // Поиск парковки по госномеру ТЕКСТОМ ($q как есть): раньше (int)"test" === 0
                // → LIKE '%0%' тянул все номера с нулём («шляпа»). comment убран (внутренние заметки).
                $query->orWhereRaw('LOWER(`auto`) LIKE ? ', [$like])
                    ->orWhereRaw('LOWER(`project`) LIKE ? ', [$like])
                    ->orWhereRaw('LOWER(`curator`) LIKE ? ', [$like]);
            })
            ->get()->toArray();

        $result = [];
        foreach ($resultRawList as $item) {
            $result[] = AutoTicketResponse::fromState($item, $q);
        }

        return $result;
    }
}
