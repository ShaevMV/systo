<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Migration;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Мигратор `order_tickets.guests` JSON из legacy формата (v2.5.x) в новый формат (v2.6.0).
 *
 * **Зачем:** в v2.6.0 каждый гость получает свой `ticket_type_id` / `promo_code` / `options[]`
 * и собственный `price_snapshot` (MoneySnapshot). Раньше эти поля были на уровне заказа.
 * См. `.claude/specs/order-format-architecture.md` §3.3.
 *
 * **Карта переноса** (legacy → new):
 *   GuestsDto {id, value, email, number, festival_id}
 *   + OrderTicket.ticket_type_id  ──┐
 *   + OrderTicket.promo_code      ──┼──→  OrderGuestLine {..., ticket_type_id, promo_code, options=[],
 *   + OrderTicket.price           ──┘     price_snapshot{base, options_sum=0, discount, total},
 *   + OrderTicket.discount                is_live_ticket}
 *   + ticket_type.is_live_ticket  ──→  OrderGuestLine.is_live_ticket
 *
 * **Распределение цены поровну между гостями:**
 *   totalPerGuest    = round((order.price - order.discount) / count(guests))
 *   discountPerGuest = round(order.discount / count(guests))
 *   basePerGuest     = totalPerGuest + discountPerGuest   ← это priceItem (цена ДО скидки)
 *
 * Округление вносит расхождение ≤ (count - 1) рублей на заказ — допустимо
 * (см. {@see OrderMigrationReport::maxAllowedRoundingError}).
 *
 * **Идемпотентность:** если у гостя уже есть `price_snapshot` — он считается мигрированным
 * и пропускается (можно перезапускать команду многократно).
 *
 * **Что НЕ делает миграция:**
 * - Не трогает `order_tickets.ticket_type_id` / `promo_code` / `price` / `discount` —
 *   эти колонки остаются (для отката и для read-моделей до aggregate-rewrite).
 * - Не валидирует структуру нового JSON через Domain VO `OrderGuestLine::fromState` —
 *   потому что Domain в master ещё не готов и валидация может сломаться на peculiar данных.
 *   Это произойдёт после aggregate-rewrite (sub-PR #5).
 *
 * Источник: Чистая архитектура — миграция как одноразовая команда вне Domain;
 * Совершенный код, гл. «Защитное программирование» — try/catch на каждый заказ, отчёт об ошибках.
 */
class OrderGuestsMigrator
{
    public function __construct(
        private ConnectionInterface $db,
    ) {
    }

    /**
     * Прогон миграции.
     *
     * @param  bool  $dryRun   true → ничего не пишем в БД, только считаем отчёт
     * @param  int   $chunkSize  обработка пачками (память)
     */
    public function migrate(bool $dryRun = true, int $chunkSize = 100): OrderMigrationReport
    {
        $report = new OrderMigrationReport();

        // Preload: ticket_type → is_live_ticket (одним запросом, чтобы избежать N+1).
        $liveByTicketType = $this->loadLiveFlagByTicketType();

        // Обрабатываем чанками — на 50k заказов разом память не закончится.
        $this->db->table('order_tickets')
            ->select(['id', 'guests', 'ticket_type_id', 'promo_code', 'price', 'discount', 'festival_id'])
            ->orderBy('id')
            ->chunk($chunkSize, function ($rows) use ($report, $liveByTicketType, $dryRun) {
                foreach ($rows as $row) {
                    $this->migrateOneOrder($row, $report, $liveByTicketType, $dryRun);
                }
            });

        return $report;
    }

    /**
     * Преобразовать guests конкретного заказа. **Чистая функция** — не пишет в БД,
     * возвращает новый массив + diff-метрики. Используется и для миграции, и для тестов.
     *
     * @param  array<int, array<string, mixed>>  $guests   текущий массив гостей (legacy или уже новый)
     * @param  array<string, bool>  $liveByTicketType   map [ticket_type_id → is_live_ticket]
     * @param  ?string  $orderFestivalId  festival_id заказа — инжектится в гостей, у которых
     *                                     его нет (legacy-формат хранил festival_id на уровне заказа,
     *                                     а OrderGuestLine::fromState требует его per-guest)
     * @return array{
     *     guests: array<int, array<string, mixed>>,
     *     migrated: bool,
     *     totalBefore: float,
     *     totalAfter: float,
     *     emptyGuests: bool,
     *     orphanTicketType: bool,
     *     clampedDiscount: bool,
     * }
     */
    public function transformOrderGuests(
        array $guests,
        ?string $ticketTypeId,
        ?string $promoCode,
        float $price,
        float $discount,
        array $liveByTicketType,
        ?string $orderFestivalId = null,
    ): array {
        if (empty($guests)) {
            return [
                'guests' => $guests,
                'migrated' => false,
                'totalBefore' => 0.0,
                'totalAfter' => 0.0,
                'emptyGuests' => true,
                'orphanTicketType' => false,
                'clampedDiscount' => false,
            ];
        }

        // Идемпотентность: заказ считается мигрированным, только если у ПЕРВОГО гостя есть
        // И price_snapshot, И festival_id. festival_id добавлен позже самого price_snapshot —
        // заказы, мигрированные ранней версией без festival_id, должны до-мигрироваться
        // (recompute детерминирован: цена берётся из тех же order.price/discount).
        if (isset($guests[0]['price_snapshot'], $guests[0]['festival_id'])) {
            return [
                'guests' => $guests,
                'migrated' => false,
                'totalBefore' => 0.0,
                'totalAfter' => 0.0,
                'emptyGuests' => false,
                'orphanTicketType' => false,
                'clampedDiscount' => false,
            ];
        }

        // Clamp: discount > price — кривые данные (напр. детский билет в статусе new с битым
        // discount). Money не может быть отрицательным → клампим discount к price (total = 0).
        // Это историческое легаси; заказ всё равно мигрируется, факт фиксируем в отчёте.
        $clampedDiscount = false;
        if ($discount > $price) {
            $discount = $price;
            $clampedDiscount = true;
        }

        // Orphan/удалённый ticket_type (старые фесты, тип удалён из каталога): тип нужен ТОЛЬКО
        // для флага is_live_ticket — цена берётся из самого заказа (order.price/discount).
        // Безопасный fallback на is_live_ticket=false (вместо падения), заказ мигрируется.
        // Факт фиксируем в отчёте (не глушим молча).
        $orphanTicketType = $ticketTypeId !== null && ! array_key_exists($ticketTypeId, $liveByTicketType);

        $count = count($guests);
        $totalBefore = $price - $discount;

        // Banker's rounding (half-to-even) — совпадает с Money::fromFloat() в Shared.
        // Без унификации после aggregate-rewrite миграция и runtime-расчёт расходились бы
        // на граничных значениях (0.5, 1.5, 2.5 ...).
        $totalPerGuest = (int) round($totalBefore / $count, 0, PHP_ROUND_HALF_EVEN);
        $discountPerGuest = (int) round($discount / $count, 0, PHP_ROUND_HALF_EVEN);
        $basePerGuest = $totalPerGuest + $discountPerGuest;  // priceItem ДО скидки

        $isLiveTicket = $ticketTypeId !== null
            && ($liveByTicketType[$ticketTypeId] ?? false);

        $totalAfter = 0.0;
        $newGuests = [];
        foreach ($guests as $guest) {
            $snapshot = [
                'base_price' => $basePerGuest,
                'options_sum' => 0,
                'discount' => $discountPerGuest,
                'total' => $totalPerGuest,
            ];

            $newGuests[] = array_merge($guest, [
                'ticket_type_id' => $ticketTypeId,
                'options' => [],
                'promo_code' => $promoCode,
                'price_snapshot' => $snapshot,
                'is_live_ticket' => $isLiveTicket,
                // festival_id обязателен для OrderGuestLine::fromState. Legacy-гости часто его
                // НЕ имели (festival_id жил на уровне заказа) → инжектим из заказа, сохраняя
                // значение гостя, если оно уже есть.
                'festival_id' => $guest['festival_id'] ?? $orderFestivalId,
            ]);

            $totalAfter += $totalPerGuest;
        }

        return [
            'guests' => $newGuests,
            'migrated' => true,
            'totalBefore' => (float) $totalBefore,
            'totalAfter' => $totalAfter,
            'emptyGuests' => false,
            'orphanTicketType' => $orphanTicketType,
            'clampedDiscount' => $clampedDiscount,
        ];
    }

    /**
     * Один заказ → отчёт.
     *
     * @param  object  $row  результат builder->get() (stdClass)
     * @param  array<string, bool>  $liveByTicketType
     */
    private function migrateOneOrder(
        object $row,
        OrderMigrationReport $report,
        array $liveByTicketType,
        bool $dryRun,
    ): void {
        $report->totalScanned++;

        try {
            $rawGuests = json_decode((string) $row->guests, true, flags: JSON_THROW_ON_ERROR);
            if (! is_array($rawGuests)) {
                throw new \RuntimeException('guests JSON не array');
            }

            $result = $this->transformOrderGuests(
                guests: $rawGuests,
                ticketTypeId: $row->ticket_type_id,
                promoCode: $row->promo_code,
                price: (float) ($row->price ?? 0.0),
                discount: (float) ($row->discount ?? 0.0),
                liveByTicketType: $liveByTicketType,
                orderFestivalId: $row->festival_id ?? null,
            );

            if ($result['emptyGuests']) {
                $report->emptyGuestsSkipped++;

                return;
            }

            if (! $result['migrated']) {
                $report->alreadyMigrated++;

                return;
            }

            $report->totalBefore += $result['totalBefore'];
            $report->totalAfter += $result['totalAfter'];
            $report->maxAllowedRoundingError += max(0, count($rawGuests) - 1);

            if ($result['orphanTicketType']) {
                $report->fallbackOrphanTicketType++;
            }
            if ($result['clampedDiscount']) {
                $report->clampedDiscount++;
            }

            if (! $dryRun) {
                $this->db->table('order_tickets')
                    ->where('id', $row->id)
                    ->update([
                        'guests' => json_encode(
                            $result['guests'],
                            JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
                        ),
                    ]);
            }

            $report->migrated++;
        } catch (Throwable $e) {
            $report->errors++;
            if (count($report->errorMessages) < 50) {
                $report->errorMessages[] = sprintf(
                    'order=%s: %s',
                    $row->id,
                    $e->getMessage(),
                );
            }
        }
    }

    /**
     * Загрузить флаг `is_live_ticket` для всех типов билетов одним запросом.
     *
     * @return array<string, bool>  ключ — `ticket_type.id`, значение — `is_live_ticket`
     */
    private function loadLiveFlagByTicketType(): array
    {
        $rows = $this->db->table('ticket_type')
            ->select(['id', 'is_live_ticket'])
            ->get();

        $map = [];
        foreach ($rows as $row) {
            $map[(string) $row->id] = (bool) $row->is_live_ticket;
        }

        return $map;
    }
}
