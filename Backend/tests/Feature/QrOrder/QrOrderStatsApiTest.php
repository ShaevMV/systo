<?php

declare(strict_types=1);

namespace Tests\Feature\QrOrder;

use App\Models\QrOrder\QrOrderModel;
use App\Models\User;
use Carbon\Carbon;
use Tests\TestCase;

/**
 * Сводные метрики qr-заказов для дашборда (POST /api/v1/qrOrder/getStats) — read-only, только admin.
 * Проверяем: доступ, итоги (заказы + выручка), разрезы по статусу/типу/дням, фильтры festival/даты.
 */
class QrOrderStatsApiTest extends TestCase
{
    private const F1 = '11111111-1111-1111-1111-111111111111';
    private const F2 = '22222222-2222-2222-2222-222222222222';

    private function makeOrder(array $overrides, ?Carbon $createdAt = null): void
    {
        $order = QrOrderModel::create(array_merge([
            'id' => (string) \Ramsey\Uuid\Uuid::uuid4(),
            'email' => 'buyer@example.com',
            'status' => 'оплачен',
            'festival_id' => self::F1,
            'type_order' => 'regular',
            'city' => 'Москва',
            'phone' => '+70000000000',
            'total_price' => 4200,
            'payload' => ['order_id' => 'x', 'guests' => []],
        ], $overrides));

        if ($createdAt !== null) {
            QrOrderModel::whereId($order->id)->update(['created_at' => $createdAt]);
        }
    }

    /**
     * 5 заказов: F1 {regular/оплачен 4200, regular/оплачен 4200, friendly/создан 4000},
     *            F2 {list/оплачен 5000, live/отменён 3000}.
     * Итого: 5 заказов, выручка 20400.
     */
    private function seedOrders(): void
    {
        $this->makeOrder(['festival_id' => self::F1, 'type_order' => 'regular', 'status' => 'оплачен', 'total_price' => 4200], Carbon::parse('2026-06-01 10:00:00'));
        $this->makeOrder(['festival_id' => self::F1, 'type_order' => 'regular', 'status' => 'оплачен', 'total_price' => 4200], Carbon::parse('2026-06-01 12:00:00'));
        $this->makeOrder(['festival_id' => self::F1, 'type_order' => 'friendly', 'status' => 'создан', 'total_price' => 4000], Carbon::parse('2026-06-02 10:00:00'));
        $this->makeOrder(['festival_id' => self::F2, 'type_order' => 'list', 'status' => 'оплачен', 'total_price' => 5000], Carbon::parse('2026-06-03 10:00:00'));
        $this->makeOrder(['festival_id' => self::F2, 'type_order' => 'live', 'status' => 'отменён', 'total_price' => 3000], Carbon::parse('2026-06-03 12:00:00'));
    }

    private function actingAsAdmin(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']), 'api');
    }

    private function sendStats(array $body = [])
    {
        return $this->postJson('/api/v1/qrOrder/getStats', $body);
    }

    /** Найти строку разреза по ключу (status/type_order) в массиве [{key, orders, revenue}]. */
    private function pick(array $rows, string $key, string $value): ?array
    {
        foreach ($rows as $row) {
            if (($row[$key] ?? null) === $value) {
                return $row;
            }
        }

        return null;
    }

    public function test_requires_authentication(): void
    {
        $this->sendStats()->assertStatus(401);
    }

    public function test_forbidden_for_non_admin(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'seller']), 'api');
        $this->sendStats()->assertStatus(403);
    }

    public function test_totals_orders_and_revenue(): void
    {
        $this->seedOrders();
        $this->actingAsAdmin();

        $this->sendStats()
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('stats.totals.orders', 5)
            ->assertJsonPath('stats.totals.revenue', 20400);
    }

    public function test_breakdown_by_status(): void
    {
        $this->seedOrders();
        $this->actingAsAdmin();

        $byStatus = $this->sendStats()->assertStatus(200)->json('stats.byStatus');

        $this->assertSame(['status' => 'оплачен', 'orders' => 3, 'revenue' => 13400], $this->pick($byStatus, 'status', 'оплачен'));
        $this->assertSame(['status' => 'создан', 'orders' => 1, 'revenue' => 4000], $this->pick($byStatus, 'status', 'создан'));
        $this->assertSame(['status' => 'отменён', 'orders' => 1, 'revenue' => 3000], $this->pick($byStatus, 'status', 'отменён'));
    }

    public function test_breakdown_by_type(): void
    {
        $this->seedOrders();
        $this->actingAsAdmin();

        $byType = $this->sendStats()->assertStatus(200)->json('stats.byType');

        $this->assertSame(['type_order' => 'regular', 'orders' => 2, 'revenue' => 8400], $this->pick($byType, 'type_order', 'regular'));
        $this->assertSame(['type_order' => 'friendly', 'orders' => 1, 'revenue' => 4000], $this->pick($byType, 'type_order', 'friendly'));
        $this->assertSame(['type_order' => 'list', 'orders' => 1, 'revenue' => 5000], $this->pick($byType, 'type_order', 'list'));
        $this->assertSame(['type_order' => 'live', 'orders' => 1, 'revenue' => 3000], $this->pick($byType, 'type_order', 'live'));
    }

    public function test_timeseries_grouped_by_day(): void
    {
        $this->seedOrders();
        $this->actingAsAdmin();

        $series = $this->sendStats()->assertStatus(200)->json('stats.timeseries');

        // 3 дня: 06-01 (2 заказа / 8400), 06-02 (1 / 4000), 06-03 (2 / 8000), отсортировано по дате.
        $this->assertCount(3, $series);
        $this->assertSame('2026-06-01', $series[0]['date']);
        $this->assertSame(2, $series[0]['orders']);
        $this->assertSame(8400, $series[0]['revenue']);
        $this->assertSame('2026-06-03', $series[2]['date']);
        $this->assertSame(8000, $series[2]['revenue']);
    }

    public function test_filter_by_festival(): void
    {
        $this->seedOrders();
        $this->actingAsAdmin();

        $this->sendStats(['filter' => ['festival_id' => self::F1]])
            ->assertStatus(200)
            ->assertJsonPath('stats.totals.orders', 3)
            ->assertJsonPath('stats.totals.revenue', 12400);
    }

    public function test_filter_by_date_range(): void
    {
        $this->seedOrders();
        $this->actingAsAdmin();

        // С 2026-06-02 включительно: friendly(06-02) + list(06-03) + live(06-03) = 3 заказа, 12000.
        $this->sendStats(['filter' => ['date_from' => '2026-06-02']])
            ->assertStatus(200)
            ->assertJsonPath('stats.totals.orders', 3)
            ->assertJsonPath('stats.totals.revenue', 12000);

        // По 2026-06-01 включительно: только два regular = 2 заказа, 8400.
        $this->sendStats(['filter' => ['date_to' => '2026-06-01']])
            ->assertStatus(200)
            ->assertJsonPath('stats.totals.orders', 2)
            ->assertJsonPath('stats.totals.revenue', 8400);
    }

    public function test_empty_dataset_returns_zero_totals(): void
    {
        $this->actingAsAdmin();

        $this->sendStats()
            ->assertStatus(200)
            ->assertJsonPath('stats.totals.orders', 0)
            ->assertJsonPath('stats.totals.revenue', 0)
            ->assertJsonPath('stats.byStatus', [])
            ->assertJsonPath('stats.timeseries', []);
    }
}
