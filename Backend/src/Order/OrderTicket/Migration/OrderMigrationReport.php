<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Migration;

/**
 * Отчёт о прогоне {@see OrderGuestsMigrator::migrate()}.
 *
 * Используется командой `order:migrate-to-guests-format` для печати таблицы итогов
 * и в тестах — для assertions без чтения вывода.
 *
 * Все суммы — в рублях с копейками (float), потому что legacy `order_tickets.price`
 * хранится как `float`. После v2.6.0 в `guests[].price_snapshot.*` всё в целых рублях
 * (int), но миграция работает с **обоими форматами**, поэтому контрольные суммы float.
 */
final class OrderMigrationReport
{
    public function __construct(
        /** Всего заказов отсмотрено */
        public int $totalScanned = 0,
        /** Заказов где guests уже в новом формате (price_snapshot есть) — пропущены идемпотентно */
        public int $alreadyMigrated = 0,
        /** Заказов с пустым `guests` (count = 0) — пропущены без ошибок */
        public int $emptyGuestsSkipped = 0,
        /** Заказов фактически мигрированных в этом прогоне */
        public int $migrated = 0,
        /** Заказов с ошибкой (битый JSON, неконсистентные поля) */
        public int $errors = 0,
        /** Заказов с fallback на is_live_ticket=false из-за orphan/удалённого ticket_type (старые фесты) */
        public int $fallbackOrphanTicketType = 0,
        /** Заказов где discount склампен к price (битые данные discount > price → total=0) */
        public int $clampedDiscount = 0,
        /** Контрольная сумма `price - discount` ДО миграции (по всем мигрируемым заказам) */
        public float $totalBefore = 0.0,
        /** Контрольная сумма `Σ price_snapshot.total` ПО ВСЕМ ГОСТЯМ ПОСЛЕ миграции */
        public float $totalAfter = 0.0,
        /** Допустимое расхождение из-за округления: ≤ (count-1) рубль на заказ */
        public float $maxAllowedRoundingError = 0.0,
        /** Подробные ошибки (массив строк, не больше 50 для нумерации) */
        public array $errorMessages = [],
    ) {
    }

    /**
     * Расхождение сумм. Если больше допустимого — миграция повредила данные.
     */
    public function roundingDelta(): float
    {
        return abs($this->totalAfter - $this->totalBefore);
    }

    public function isRoundingWithinTolerance(): bool
    {
        return $this->roundingDelta() <= $this->maxAllowedRoundingError;
    }

    /**
     * Краткое описание прогона — для вывода в команде.
     *
     * @return array<int, array{string, int|string|float}>  пары [метрика, значение] для $this->table()
     */
    public function toTableRows(): array
    {
        return [
            ['Всего отсмотрено', $this->totalScanned],
            ['Уже мигрированы (idempotent skip)', $this->alreadyMigrated],
            ['Пустые guests (skip)', $this->emptyGuestsSkipped],
            ['Фактически мигрировано', $this->migrated],
            ['Ошибок', $this->errors],
            ['Fallback orphan ticket_type (is_live=false)', $this->fallbackOrphanTicketType],
            ['Склампен discount>price (total=0)', $this->clampedDiscount],
            ['Сумма ДО (price - discount)', number_format($this->totalBefore, 2, '.', ' ')],
            ['Сумма ПОСЛЕ (Σ price_snapshot.total)', number_format($this->totalAfter, 2, '.', ' ')],
            ['Расхождение (округление)', number_format($this->roundingDelta(), 2, '.', ' ')],
            ['Допустимое расхождение', number_format($this->maxAllowedRoundingError, 2, '.', ' ')],
            ['Расхождение в допуске?', $this->isRoundingWithinTolerance() ? '✅ да' : '❌ НЕТ — данные повреждены'],
        ];
    }
}
