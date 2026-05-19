<?php

declare(strict_types=1);

namespace Baza\Sync\Repositories;

use App\Models\AutoModel;
use App\Models\ChangesModel;
use App\Models\ElTicketsModel;
use App\Models\LiveTicketModel;
use App\Models\ParkingTicketModel;
use App\Models\SpisokTicketModel;
use Carbon\Carbon;
use InvalidArgumentException;

class InMemoryMySqlSyncRepository implements SyncRepositoryInterface
{
    /**
     * Белый список таблиц → класс Eloquent-модели.
     * Порядок важен только при импорте (changes — раньше прочих, на случай
     * если когда-нибудь добавят физический FK по change_id).
     */
    private const TABLE_MODEL_MAP = [
        ChangesModel::TABLE        => ChangesModel::class,
        ElTicketsModel::TABLE      => ElTicketsModel::class,
        LiveTicketModel::TABLE     => LiveTicketModel::class,
        ParkingTicketModel::TABLE  => ParkingTicketModel::class,
        SpisokTicketModel::TABLE   => SpisokTicketModel::class,
        AutoModel::TABLE           => AutoModel::class,
    ];

    public function getSyncTables(): array
    {
        return array_keys(self::TABLE_MODEL_MAP);
    }

    public function chunkForExport(string $table, ?Carbon $since, int $chunkSize, callable $callback): void
    {
        $modelClass = $this->resolveModel($table);

        $query = $modelClass::query()->orderBy('id');

        if ($since !== null) {
            $query->where('updated_at', '>', $since);
        }

        $query->chunk($chunkSize, function ($items) use ($callback) {
            foreach ($items as $item) {
                $callback($item->toArray());
            }
        });
    }

    public function findById(string $table, int $id): ?array
    {
        $modelClass = $this->resolveModel($table);

        return $modelClass::query()->find($id)?->toArray();
    }

    public function insert(string $table, array $data): bool
    {
        $modelClass = $this->resolveModel($table);

        return $modelClass::query()->insert($this->normalize($data));
    }

    public function update(string $table, int $id, array $data): bool
    {
        $modelClass = $this->resolveModel($table);

        unset($data['id']);

        return $modelClass::query()->where('id', $id)->update($this->normalize($data)) >= 0;
    }

    /**
     * Нормализует значения перед сырым insert/update: Eloquent при toArray()
     * сериализует timestamps в ISO-8601 (Y-m-d\TH:i:s.uP), а MySQL принимает
     * только 'Y-m-d H:i:s'. Здесь конвертируем все ISO-строки обратно.
     *
     * Без срабатывания Eloquent-кастов timestamps пройдут «как есть» — поэтому
     * нормализация делается в инфраструктурном слое (репозитории).
     */
    private function normalize(array $data): array
    {
        foreach ($data as $key => $value) {
            if (!is_string($value)) {
                continue;
            }
            if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $value) === 1) {
                $data[$key] = Carbon::parse($value)->format('Y-m-d H:i:s');
            }
        }

        return $data;
    }

    /**
     * Защита от инъекции имени таблицы — работаем только с белым списком.
     */
    private function resolveModel(string $table): string
    {
        if (!isset(self::TABLE_MODEL_MAP[$table])) {
            throw new InvalidArgumentException("Таблица '{$table}' не разрешена для синхронизации");
        }

        return self::TABLE_MODEL_MAP[$table];
    }
}
