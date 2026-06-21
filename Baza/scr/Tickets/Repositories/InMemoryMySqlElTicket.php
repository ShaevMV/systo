<?php

namespace Baza\Tickets\Repositories;

use App\Models\ElTicketsModel;
use Shared\Domain\ValueObject\Uuid;
use Baza\Tickets\Responses\ElTicketResponse;
use Carbon\Carbon;
use DB;
use Throwable;

class InMemoryMySqlElTicket implements ElTicketsRepositoryInterface
{
    // UUID текущего фестиваля зашит прямо в коде — фильтр, чтобы на входе показывались
    // билеты только актуального события. Дублируется в Spisok/Friendly/Auto-репозиториях,
    // в SaveChange и в отчёте смен. В Live и Parking фильтра по festival_id НЕТ вообще.
    // Кандидат на вынос в конфиг/env — меняется каждый фестиваль. См. .claude/docs/BAZA.md §9.
    private const UUID_FESTIVAL = '9d679bcf-b438-4ddb-ac04-023fa9bff4b8';

    public function __construct(
        private ElTicketsModel $elTicketsModel,
        private ?string        $festivalId = self::UUID_FESTIVAL,
    )
    {
    }

    private function addFestivalUuid()
    {
        if ($this->festivalId) {
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
     * Пометить билет впущенным на КПП.
     *
     * ВНИМАНИЕ: параметр $userId на деле — это changeId (id открытой смены), а НЕ id
     * пользователя. Он пишется в колонку change_id, которая одновременно служит флагом
     * «гость впущен» (NULL = ещё не входил). Имя параметра вводит в заблуждение —
     * кандидат на переименование в $changeId.
     * Билет здесь ищется по kilter (число из QR), тогда как search() — по uuid: разные ключи.
     *
     * @throws Throwable
     */
    public function skip(int $id, int $userId): bool
    {
        $rawData = $this->addFestivalUuid()->whereKilter($id)
            ->first();

        // Серверная защита от повторного впуска: билет должен существовать и ещё не быть
        // пропущенным (date_change пуст). Раньше проверка жила только на фронте — повторный
        // запрос перезаписывал отметку и накручивал счётчик смены.
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

        $resultRawList = $this->addFestivalUuid()
            ->where(function ($query) use ($q, $like) {
                $query->orWhereRaw('LOWER(`name`) LIKE ? ', [$like])
                    ->orWhereRaw('LOWER(`email`) LIKE ? ', [$like])
                    ->orWhereRaw('LOWER(`phone`) LIKE ? ', [$like]);
                // Поиск по номеру билета — ТОЛЬКО для числового запроса. Иначе (int)"test" === 0
                // и whereKilter(0) тянул бы нерелевантные билеты («шляпа»). comment убран из
                // гостевого поиска (внутренние заметки персонала, не идентификация гостя).
                if (ctype_digit($q)) {
                    $query->orWhere('kilter', (int) $q);
                }
            })
            ->get()->toArray();

        $result = [];
        foreach ($resultRawList as $item) {
            $result[] = ElTicketResponse::fromState($item, $q);
        }

        return $result;
    }
}
