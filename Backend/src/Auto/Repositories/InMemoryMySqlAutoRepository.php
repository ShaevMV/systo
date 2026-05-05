<?php

declare(strict_types=1);

namespace Tickets\Auto\Repositories;

use App\Models\Auto\AutoModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Shared\Domain\ValueObject\Uuid;
use Tickets\Auto\Dto\AutoDto;

final class InMemoryMySqlAutoRepository implements AutoRepositoryInterface
{
    public function __construct(
        private AutoModel $model,
    ) {
    }

    public function create(AutoDto $auto): bool
    {
        return $this->model::query()->insert($auto->toArrayForCreate() + [
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function delete(Uuid $autoId): bool
    {
        return (bool) $this->model::query()->whereId($autoId->value())->delete();
    }

    public function getById(Uuid $autoId): ?AutoDto
    {
        $row = $this->model::query()->whereId($autoId->value())->first();
        return $row ? AutoDto::fromState($row->toArray()) : null;
    }

    public function getByOrderId(Uuid $orderTicketId): array
    {
        return $this->model::query()
            ->whereOrderTicketId($orderTicketId->value())
            ->orderBy('created_at')
            ->get()
            ->map(fn (AutoModel $row) => AutoDto::fromState($row->toArray()))
            ->all();
    }

    public function setInBazaAuto(AutoDto $auto, string $curator, string $project, ?Uuid $festivalId): bool
    {
        try {
            DB::connection('mysqlBaza')->getPdo();
            DB::connection('mysqlBaza')
                ->table('auto')
                ->insert([
                    'curator'     => $curator,
                    'project'     => $project,
                    'auto'        => $auto->number,
                    'festival_id' => $festivalId?->value(),
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
        } catch (\Exception $e) {
            Log::error('setInBazaAuto: ' . $e->getMessage(), ['auto_id' => $auto->id->value()]);
            return false;
        } finally {
            return true;
        }
    }

    public function removeFromBazaAuto(AutoDto $auto, string $curator, string $project, ?Uuid $festivalId): bool
    {
        try {
            DB::connection('mysqlBaza')->getPdo();

            // Дубли допускаются, удаляем одну запись по сигнатуре.
            $row = DB::connection('mysqlBaza')
                ->table('auto')
                ->where('festival_id', '=', $festivalId?->value())
                ->where('curator', '=', $curator)
                ->where('project', '=', $project)
                ->where('auto', '=', $auto->number)
                ->orderByDesc('created_at')
                ->first();

            if ($row !== null) {
                DB::connection('mysqlBaza')
                    ->table('auto')
                    ->where('id', '=', $row->id)
                    ->delete();
            }
        } catch (\Exception $e) {
            Log::error('removeFromBazaAuto: ' . $e->getMessage(), ['auto_id' => $auto->id->value()]);
            return false;
        } finally {
            return true;
        }
    }
}
