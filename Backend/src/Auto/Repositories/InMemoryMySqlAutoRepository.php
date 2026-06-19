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

    public function setInBazaAuto(AutoDto $auto, ?Uuid $festivalId): bool
    {
        try {
            DB::connection('mysqlBaza')->getPdo();
            // Идемпотентно по (order_id, auto): повторная доставка (авто-ретрай/resend) не плодит дубли.
            DB::connection('mysqlBaza')
                ->table('auto')
                ->updateOrInsert(
                    ['order_id' => $auto->orderTicketId->value(), 'auto' => $auto->number],
                    [
                        'curator'     => (string) ($auto->curator ?? ''),
                        'project'     => (string) ($auto->project ?? ''),
                        'festival_id' => $festivalId?->value(),
                        'created_at'  => now(),
                        'updated_at'  => now(),
                    ],
                );

            return true;
        } catch (\Throwable $e) {
            // Возвращаем реальный результат (раньше finally{return true} глушил сбой) —
            // чтобы DeliverTicketToBazaJob увидел ошибку и ретраил.
            Log::error('setInBazaAuto: ' . $e->getMessage(), ['auto_id' => $auto->id->value()]);

            return false;
        }
    }

    public function removeAllFromBazaByOrderId(Uuid $orderId): bool
    {
        try {
            DB::connection('mysqlBaza')->getPdo();
            DB::connection('mysqlBaza')
                ->table('auto')
                ->where('order_id', '=', $orderId->value())
                ->delete();

            return true;
        } catch (\Throwable $e) {
            Log::error('removeAllFromBazaByOrderId: ' . $e->getMessage(), ['order_id' => $orderId->value()]);

            return false;
        }
    }
}
