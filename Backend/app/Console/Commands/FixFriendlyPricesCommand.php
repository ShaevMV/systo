<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Ordering\OrderTicketModel;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Command\Command as CommandAlias;
use Throwable;

/**
 * Корректирует завышенные суммы Friendly-заказов.
 *
 * Правильная сумма зависит от волны цен:
 *   - заказ создан до 12 мая 2026 включительно → 5900 ₽ за билет
 *   - заказ создан с 13 мая 2026                → 6500 ₽ за билет
 *
 * Если фактическая сумма > (count * корректная_цена) — заменяем на правильную.
 *
 * Защита от случайной правки: по умолчанию dry-run, реально пишет только с --apply.
 *
 * Одноразовый скрипт фикса — по решению автора работаем с моделью напрямую
 * (как в существующих командах AddPriceForTypeTicket / CheckCreateTicket).
 */
class FixFriendlyPricesCommand extends Command
{
    protected $signature = 'orders:fix-friendly-prices
        {--apply : Реально применить изменения. Без флага — dry-run.}';

    protected $description = 'Фикс завышенных сумм Friendly-заказов (5900 ₽ до 12.05.2026, 6500 ₽ с 13.05.2026)';

    /**
     * Граница волны цен (включительно).
     * created_at <= этой даты → 5900; created_at > этой даты → 6500.
     */
    private const PRICE_WAVE_CUTOFF = '2026-05-12 23:59:59';

    private const PRICE_BEFORE = 5900;
    private const PRICE_AFTER  = 6500;

    public function handle(): int
    {
        $apply = (bool)$this->option('apply');
        $cutoff = Carbon::parse(self::PRICE_WAVE_CUTOFF);

        $this->info($apply ? 'Режим: APPLY (запись в БД)' : 'Режим: DRY-RUN (без записи). Для применения добавь --apply');
        $this->info('Граница волны цен: ' . $cutoff->toDateTimeString() . ' (включительно → 5900, после → 6500)');
        $this->newLine();

        $orders = OrderTicketModel::query()
            ->whereNotNull('friendly_id')
            ->whereNull('curator_id')
            ->get(['id', 'guests', 'price', 'created_at']);

        $this->info('Всего friendly-заказов: ' . $orders->count());

        $toFix = [];
        foreach ($orders as $order) {
            $guestsRaw = is_array($order->guests)
                ? $order->guests
                : (json_decode((string)$order->guests, true) ?: []);
            $count = count($guestsRaw);

            $correctUnitPrice = $order->created_at->gt($cutoff) ? self::PRICE_AFTER : self::PRICE_BEFORE;
            $correctTotal = $count * $correctUnitPrice;

            if ((float)$order->price > $correctTotal) {
                $toFix[] = [
                    'id'           => (string)$order->id,
                    'created_at'   => $order->created_at->toDateTimeString(),
                    'count'        => $count,
                    'price_was'    => (float)$order->price,
                    'unit_correct' => $correctUnitPrice,
                    'price_correct'=> $correctTotal,
                    'diff'         => (float)$order->price - $correctTotal,
                ];
            }
        }

        if (empty($toFix)) {
            $this->info('Завышенных сумм не найдено — ничего фиксить не нужно.');
            return CommandAlias::SUCCESS;
        }

        $this->warn('К фиксу подлежат: ' . count($toFix) . ' заказ(ов)');
        $this->newLine();

        $rows = array_map(static fn (array $r) => [
            $r['id'],
            $r['created_at'],
            $r['count'],
            $r['price_was'],
            $r['unit_correct'],
            $r['price_correct'],
            $r['diff'],
        ], $toFix);

        $this->table(
            ['id', 'created_at', 'count', 'price (было)', 'unit (правильная)', 'price (станет)', 'diff'],
            $rows
        );

        $totalDiff = array_sum(array_column($toFix, 'diff'));
        $this->info('Суммарное «было - станет»: ' . $totalDiff . ' ₽');

        if (!$apply) {
            $this->newLine();
            $this->warn('DRY-RUN: изменения НЕ записаны. Запусти с --apply для применения.');
            return CommandAlias::SUCCESS;
        }

        $this->newLine();
        $this->info('Применяем изменения…');

        $applied = 0;
        $failed = 0;
        foreach ($toFix as $item) {
            DB::beginTransaction();
            try {
                $order = OrderTicketModel::query()->find($item['id']);
                $order->price = $item['price_correct'];
                $order->discount = 0;
                $order->save();
                DB::commit();
                $applied++;
            } catch (Throwable $e) {
                DB::rollBack();
                $failed++;
                $this->error('Ошибка на заказе ' . $item['id'] . ': ' . $e->getMessage());
            }
        }

        $this->newLine();
        $this->info("Применено: {$applied}, ошибок: {$failed}");

        return $failed > 0 ? CommandAlias::FAILURE : CommandAlias::SUCCESS;
    }
}
