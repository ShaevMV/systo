<?php

declare(strict_types=1);

namespace Baza\Tickets\Repositories;

use App\Models\FriendlyTicketModel;
use Baza\Festival\Services\FestivalScope;
use Baza\Tickets\Responses\FriendlyTicketResponse;
use Carbon\Carbon;
use DB;
use Throwable;

class InMemoryMySqlFriendlyTicket implements FriendlyTicketRepositoryInterface
{
    public function __construct(
        private FriendlyTicketModel $friendlyTicketModel,
        private FestivalScope       $festivalScope,
    )
    {
    }


    public function search(int $kilter): ?FriendlyTicketResponse
    {
        $data = $this->festivalScope
            ->apply($this->friendlyTicketModel::whereKilter($kilter))
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
        $rawData = $this->festivalScope
            ->apply($this->friendlyTicketModel::whereKilter($id))
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

        $resultRawList = $this->festivalScope->apply($this->friendlyTicketModel->newQuery())
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
