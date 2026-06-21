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
    private const UUID_FESTIVAL = '9d679bcf-b438-4ddb-ac04-023fa9bff4b8';


    public function __construct(
        private FriendlyTicketModel $friendlyTicketModel
    )
    {
    }


    public function search(int $kilter): ?FriendlyTicketResponse
    {
        $data = $this->friendlyTicketModel::whereKilter($kilter)
            ->whereFestivalId(self::UUID_FESTIVAL)
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
            ->whereFestivalId(self::UUID_FESTIVAL)
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

        $resultRawList = $this->friendlyTicketModel::whereFestivalId(self::UUID_FESTIVAL)
                ->where(function($query) use ($q, $like) {
                    // Поиск по ВСЕМ полям (решение владельца): ФИО/проект/email/коммент.
                    $query->orWhereRaw('LOWER(`name`) LIKE ? ', [$like])
                        ->orWhereRaw('LOWER(`project`) LIKE ? ', [$like])
                        ->orWhereRaw('LOWER(`email`) LIKE ? ', [$like])
                        ->orWhereRaw('LOWER(`comment`) LIKE ? ', [$like]);
                    // Номер билета — только для числового запроса (иначе (int)"test" === 0).
                    if (ctype_digit($q)) {
                        $query->orWhere('kilter', (int) $q);
                    }
                })
            ->get()
            ->toArray();

        $result = [];
        foreach ($resultRawList as $item) {
            $result[] = FriendlyTicketResponse::fromState($item,$q);
        }

        return $result;
    }
}
