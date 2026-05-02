<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Shared\Domain\ValueObject\Status;
use Throwable;

/**
 * Разовая миграция данных из устаревшей таблицы order_tickets
 * в новые типизированные таблицы guest_orders, friendly_orders, live_orders.
 *
 * Правила разбивки:
 *   friendly_id IS NOT NULL           → friendly_orders
 *   status IN (live-статусы)          → live_orders
 *   всё остальное                     → guest_orders
 *
 * Kilter сохраняется: MySQL принимает явное значение в AUTO_INCREMENT колонке.
 * После вставки AUTO_INCREMENT сбрасывается на MAX(kilter)+1.
 *
 * Использование:
 *   php artisan orders:migrate-from-legacy           # реальный запуск
 *   php artisan orders:migrate-from-legacy --dry-run # только статистика
 *   php artisan orders:migrate-from-legacy --chunk=50
 */
final class MigrateOrdersFromLegacy extends Command
{
    protected $signature = 'orders:migrate-from-legacy
        {--dry-run : Только показать статистику, без записи в БД}
        {--chunk=200 : Размер чанка для обработки}';

    protected $description = 'Мигрировать order_tickets → guest_orders / friendly_orders / live_orders';

    private const LIVE_STATUSES = [
        Status::NEW_FOR_LIVE,
        Status::PAID_FOR_LIVE,
        Status::LIVE_TICKET_ISSUED,
        Status::CANCEL_FOR_LIVE,
    ];

    private int $guestCount    = 0;
    private int $friendlyCount = 0;
    private int $liveCount     = 0;
    private int $skipCount     = 0;

    public function handle(): int
    {
        $dryRun = (bool)$this->option('dry-run');
        $chunk  = (int)$this->option('chunk');

        if ($dryRun) {
            $this->info('[DRY-RUN] Изменения в БД не сохраняются.');
        }

        $total = DB::table('order_tickets')->count();
        $this->info("Всего записей в order_tickets: {$total}");

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        DB::table('order_tickets')
            ->orderBy('kilter')
            ->chunk($chunk, function ($rows) use ($dryRun, $bar): void {
                $guests    = [];
                $friendlys = [];
                $lives     = [];

                foreach ($rows as $row) {
                    $type = $this->detectType($row);

                    match ($type) {
                        'friendly' => $friendlys[] = $this->toFriendlyRow($row),
                        'live'     => $lives[]     = $this->toLiveRow($row),
                        'guest'    => $guests[]    = $this->toGuestRow($row),
                        default    => $this->skipCount++,
                    };
                }

                if (!$dryRun) {
                    $this->insertChunk('guest_orders',    $guests);
                    $this->insertChunk('friendly_orders', $friendlys);
                    $this->insertChunk('live_orders',     $lives);
                }

                $this->guestCount    += count($guests);
                $this->friendlyCount += count($friendlys);
                $this->liveCount     += count($lives);

                $bar->advance(count($guests) + count($friendlys) + count($lives) + $this->skipCount);
            });

        $bar->finish();
        $this->newLine(2);

        $this->table(
            ['Таблица', 'Записей'],
            [
                ['guest_orders',    $this->guestCount],
                ['friendly_orders', $this->friendlyCount],
                ['live_orders',     $this->liveCount],
                ['пропущено',       $this->skipCount],
            ]
        );

        if (!$dryRun) {
            $this->resetAutoIncrement();
        }

        $this->info($dryRun ? 'Dry-run завершён.' : 'Миграция завершена.');

        return self::SUCCESS;
    }

    private function detectType(object $row): string
    {
        if (!empty($row->friendly_id)) {
            return 'friendly';
        }

        if (in_array($row->status, self::LIVE_STATUSES, true)) {
            return 'live';
        }

        return 'guest';
    }

    private function toGuestRow(object $row): array
    {
        return [
            'kilter'              => $row->kilter,
            'id'                  => $row->id,
            'festival_id'         => $row->festival_id,
            'user_id'             => $row->user_id,
            'ticket_type_id'      => $row->ticket_type_id,
            'types_of_payment_id' => $row->types_of_payment_id,
            'ticket'              => $row->guests,       // JSON — формат совместим
            'status'              => $row->status,
            'price'               => $row->price,
            'discount'            => $row->discount,
            'promo_code'          => $row->promo_code,
            'phone'               => $row->phone ?? '',
            'id_buy'              => $row->id_buy ?? null,
            'created_at'          => $row->created_at,
            'updated_at'          => $row->updated_at,
        ];
    }

    private function toFriendlyRow(object $row): array
    {
        return [
            'kilter'         => $row->kilter,
            'id'             => $row->id,
            'festival_id'    => $row->festival_id,
            'user_id'        => $row->user_id,     // user_id = pusher в старой системе
            'ticket_type_id' => $row->ticket_type_id,
            'ticket'         => $row->guests,
            'status'         => $row->status,
            'price'          => $row->price,
            'created_at'     => $row->created_at,
            'updated_at'     => $row->updated_at,
        ];
    }

    private function toLiveRow(object $row): array
    {
        return [
            'kilter'              => $row->kilter,
            'id'                  => $row->id,
            'festival_id'         => $row->festival_id,
            'user_id'             => $row->user_id,
            'ticket_type_id'      => $row->ticket_type_id,
            'types_of_payment_id' => $row->types_of_payment_id,
            'ticket'              => $row->guests,
            'status'              => $row->status,
            'price'               => $row->price,
            'discount'            => $row->discount,
            'promo_code'          => $row->promo_code,
            'phone'               => $row->phone ?? '',
            'id_buy'              => $row->id_buy ?? null,
            'created_at'          => $row->created_at,
            'updated_at'          => $row->updated_at,
        ];
    }

    private function insertChunk(string $table, array $rows): void
    {
        if (empty($rows)) {
            return;
        }

        try {
            // INSERT IGNORE пропускает дубликаты (повторный запуск безопасен)
            DB::table($table)->insertOrIgnore($rows);
        } catch (Throwable $e) {
            $this->error("Ошибка при вставке в {$table}: " . $e->getMessage());
        }
    }

    private function resetAutoIncrement(): void
    {
        foreach (['guest_orders', 'friendly_orders', 'live_orders'] as $table) {
            $max = DB::table($table)->max('kilter');
            if ($max !== null) {
                $next = (int)$max + 1;
                DB::statement("ALTER TABLE {$table} AUTO_INCREMENT = {$next}");
                $this->line("  {$table}: AUTO_INCREMENT сброшен на {$next}");
            }
        }
    }
}
