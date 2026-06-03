<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\ConnectionInterface;
use Symfony\Component\Console\Command\Command as CommandAlias;
use Throwable;
use Tickets\Order\OrderTicket\Migration\OrderGuestsBackupService;
use Tickets\Order\OrderTicket\Migration\OrderGuestsMigrator;

/**
 * Миграция формата `order_tickets.guests` JSON из v2.5.x в v2.6.0.
 *
 * Под капотом: {@see OrderGuestsMigrator} (логика трансформации) + {@see OrderGuestsBackupService}
 * (CREATE TABLE AS SELECT перед началом).
 *
 * Безопасность:
 * - **По умолчанию dry-run** (никаких изменений в БД). Реально пишет только с `--apply`.
 * - Перед `--apply` обязательный inline-бэкап `order_tickets_backup_v2_6_0_<timestamp>`.
 *   Отключается флагом `--no-backup` (только для повторных прогонов после первого бэкапа).
 * - Идемпотентна: повторный запуск пропускает уже мигрированные заказы.
 *
 * Использование:
 *   php artisan order:migrate-to-guests-format                  # dry-run, печатает что будет
 *   php artisan order:migrate-to-guests-format --apply          # реально пишет (с бэкапом)
 *   php artisan order:migrate-to-guests-format --apply --no-backup  # повторный прогон без бэкапа
 *   php artisan order:migrate-to-guests-format --chunk=500     # размер пачки для chunk()
 *
 * См. `.claude/docs/process/migrations/v2.6.0.md`.
 */
class MigrateOrdersToGuestsFormatCommand extends Command
{
    protected $signature = 'order:migrate-to-guests-format
        {--apply : Реально применить изменения в БД. Без флага — dry-run.}
        {--no-backup : Не создавать inline-бэкап таблицы (только с --apply, для повторных прогонов).}
        {--force : Пропустить интерактивные подтверждения (для CI/CD без TTY).}
        {--chunk=100 : Размер пачки для построчного обхода (default 100).}';

    protected $description = 'Мигрировать order_tickets.guests JSON в формат v2.6.0 (per-guest ticket_type / promo / price_snapshot)';

    public function handle(
        ConnectionInterface $connection,
        OrderGuestsMigrator $migrator,
        OrderGuestsBackupService $backup,
    ): int {
        $apply = (bool) $this->option('apply');
        $noBackup = (bool) $this->option('no-backup');
        $force = (bool) $this->option('force');
        $chunkSize = max(1, (int) $this->option('chunk'));

        $mode = $apply ? '🔥 APPLY (реальная миграция)' : '🧪 DRY-RUN (изменений в БД не будет)';
        $this->info(sprintf('Режим: %s', $mode));
        $this->info(sprintf('Размер пачки (chunk): %d', $chunkSize));

        // ─── 1. Inline-бэкап (только в режиме --apply, без --no-backup) ───
        $backupTable = null;
        if ($apply && ! $noBackup) {
            try {
                $timestamp = date('Y_m_d_His');
                $this->info('');
                $this->info(sprintf('Создаю бэкап-таблицу с timestamp %s ...', $timestamp));
                $backupTable = $backup->createBackup($timestamp);

                $sourceCount = $backup->countRowsInOrderTickets();
                $backupCount = $backup->countRowsInBackup($backupTable);

                if ($sourceCount !== $backupCount) {
                    $this->error(sprintf(
                        'Бэкап неконсистентен: source=%d, backup=%d. Прерываю.',
                        $sourceCount, $backupCount,
                    ));

                    return CommandAlias::FAILURE;
                }
                $this->info(sprintf(
                    '✅ Бэкап готов: %s (%d строк). Для отката: TRUNCATE order_tickets; INSERT INTO order_tickets SELECT * FROM `%s`;',
                    $backupTable, $backupCount, $backupTable,
                ));
            } catch (Throwable $e) {
                $this->error(sprintf('Не удалось создать бэкап: %s', $e->getMessage()));

                return CommandAlias::FAILURE;
            }
        } elseif ($apply && $noBackup) {
            // В CI/CD `$this->confirm()` зависает без TTY — обходим через --force.
            // Без --force и в неинтерактивной среде падаем явно (а не молча).
            if ($force) {
                $this->warn('--no-backup + --force: inline-бэкап пропущен по флагу.');
            } else {
                if (! $this->input->isInteractive()) {
                    $this->error('--no-backup в неинтерактивной среде требует --force (иначе нельзя подтвердить).');

                    return CommandAlias::FAILURE;
                }
                if (! $this->confirm('Точно пропустить inline-бэкап? Откатить миграцию будет невозможно.', false)) {
                    $this->warn('Прервано пользователем.');

                    return CommandAlias::SUCCESS;
                }
            }
        }

        // ─── 2. Прогон миграции ───
        $this->info('');
        $this->info('Запускаю миграцию ...');

        try {
            // Транзакция только в режиме --apply: dry-run не пишет в БД, транзакция не нужна.
            $report = $apply
                ? $connection->transaction(static fn () => $migrator->migrate(dryRun: false, chunkSize: $chunkSize))
                : $migrator->migrate(dryRun: true, chunkSize: $chunkSize);
        } catch (Throwable $e) {
            $this->error(sprintf('Миграция упала: %s', $e->getMessage()));
            $this->error('Транзакция откачена — данные не изменились.');
            if ($backupTable !== null) {
                $this->warn(sprintf('Бэкап-таблица %s осталась — можно удалить вручную после анализа.', $backupTable));
            }

            return CommandAlias::FAILURE;
        }

        // ─── 3. Итоги ───
        $this->info('');
        $this->table(['Метрика', 'Значение'], $report->toTableRows());

        if (! empty($report->errorMessages)) {
            $this->newLine();
            $this->warn(sprintf('Ошибок (первые %d):', count($report->errorMessages)));
            foreach ($report->errorMessages as $msg) {
                $this->line('  • ' . $msg);
            }
        }

        if (! $report->isRoundingWithinTolerance()) {
            $this->newLine();
            $this->error('❌ Расхождение сумм ВЫШЕ допустимого порога округления. ДАННЫЕ ПОВРЕЖДЕНЫ.');

            return CommandAlias::FAILURE;
        }

        // Любые ошибки в отчёте = FAILURE, даже если суммы сошлись.
        // Один пропущенный заказ — это уже инцидент, нужно ручное вмешательство.
        if ($report->errors > 0) {
            $this->newLine();
            $this->error(sprintf(
                '❌ Ошибок при миграции: %d. См. список выше. Команда возвращает FAILURE.',
                $report->errors,
            ));

            return CommandAlias::FAILURE;
        }

        if ($apply) {
            $this->newLine();
            $this->info('✅ Миграция применена.');
        } else {
            $this->newLine();
            $this->comment('Это был dry-run. Запустите с --apply чтобы реально применить.');
        }

        return CommandAlias::SUCCESS;
    }
}
