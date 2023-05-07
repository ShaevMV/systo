<?php

declare(strict_types=1);

namespace Baza\Changes\Repositories;

use App\Models\ChangesModel;
use Carbon\Carbon;
use DomainException;

class InMemoryMySqlChangesRepository implements ChangesRepositoryInterface
{
    public function __construct(
        private ChangesModel $model,
    )
    {
    }


    public function getAllReport(): array
    {
        //$this->user::leftJoin()
    }

    public function open(int $userId): int
    {
        /** @var ChangesModel $result */
        $result = $this->model::firstOrCreate([
            'user_id' => $userId,
            'end' => null
        ]);

        if ($result->start === null) {
            $result->start = new Carbon();
            $result->save();
        }

        return $result->id;
    }

    public function close(int $userId): int
    {
        /** @var ChangesModel $result */
        $result = $this->model::whereUserId($userId)
            ->whereEnd(null)
            ->first();

        $result->end = new Carbon();
        $result->save();

        return $result->id;
    }

    public function addTicket(string $typeTickets): bool
    {
        // TODO: Implement addTicket() method.
    }

    public function getChangeId(int $userId): ?int
    {
        $result = $this->model::whereUserId($userId)
            ->whereEnd(null)
            ->first();

        return $result?->id;
    }
}
