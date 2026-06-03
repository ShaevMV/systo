<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Migration;

use Illuminate\Database\ConnectionInterface;

/**
 * Бэкап таблицы `order_tickets` перед миграцией формата гостей в v2.6.0.
 *
 * Стратегия: `CREATE TABLE order_tickets_backup_v2_6_0_<YYYY_mm_dd_His> AS SELECT * FROM order_tickets`.
 * Это создаёт независимую копию данных в той же БД — для отката достаточно
 * `INSERT INTO order_tickets SELECT * FROM <backup_table>` (после `TRUNCATE`).
 *
 * **Дополнительные копии БД** (5 штук, как требует `.claude/specs/order-format-architecture.md` §3.3)
 * делаются на уровне CI/CD пайплайна через `mysqldump` перед запуском artisan-команды
 * (см. `.claude/docs/process/migrations/v2.6.0.md`). Сервис обеспечивает только одну
 * inline-копию — последний рубеж защиты на случай если pipeline-бэкапы не сработают.
 *
 * Имя таблицы намеренно содержит timestamp до секунды — повторный запуск создаёт новую
 * таблицу, старые не удаляются (ручная чистка через несколько дней после успешного релиза).
 */
class OrderGuestsBackupService
{
    public function __construct(
        private ConnectionInterface $db,
    ) {
    }

    /**
     * Создать бэкап-таблицу. Возвращает имя созданной таблицы для логирования.
     *
     * @param  string  $timestamp  опционально — для тестов чтобы зафиксировать имя
     * @throws \RuntimeException если таблица с таким именем уже существует
     */
    public function createBackup(string $timestamp): string
    {
        $backupTable = $this->buildBackupTableName($timestamp);

        // Проверка: если по случайности уже есть таблица с таким именем — не перезаписываем,
        // лучше сразу падать. Это маловероятно (timestamp до секунды), но дёшевая защита.
        $exists = $this->db->select(
            'SHOW TABLES LIKE ?',
            [$backupTable],
        );
        if (! empty($exists)) {
            throw new \RuntimeException(sprintf(
                'Backup-таблица %s уже существует — удалите её перед повторным запуском',
                $backupTable,
            ));
        }

        // `CREATE TABLE ... AS SELECT` — копирует ДАННЫЕ и колонки, но НЕ ключи/индексы.
        // Для бэкапа этого достаточно (откат через INSERT работает на любых данных).
        $this->db->statement(sprintf(
            'CREATE TABLE `%s` AS SELECT * FROM `order_tickets`',
            $backupTable,
        ));

        return $backupTable;
    }

    /**
     * Сосчитать строки в бэкап-таблице — для проверки что бэкап непустой.
     */
    public function countRowsInBackup(string $backupTable): int
    {
        $result = $this->db->select(sprintf(
            'SELECT COUNT(*) AS cnt FROM `%s`',
            $backupTable,
        ));

        return (int) ($result[0]->cnt ?? 0);
    }

    /**
     * Сосчитать строки в исходной таблице — для сверки.
     */
    public function countRowsInOrderTickets(): int
    {
        return (int) $this->db->table('order_tickets')->count();
    }

    public function buildBackupTableName(string $timestamp): string
    {
        // Защита от инъекций — оставляем только [a-zA-Z0-9_].
        $safe = preg_replace('/[^a-zA-Z0-9_]/', '_', $timestamp);

        return sprintf('order_tickets_backup_v2_6_0_%s', $safe);
    }
}
