<?php

declare(strict_types=1);

namespace Tests\Feature\QrOrder;

use App\Models\QrOrder\QrOrderModel;
use App\Models\User;
use Carbon\Carbon;
use Tests\TestCase;

/**
 * Список qr-заказов для админки (POST /api/v1/qrOrder/getList) — read-only, только admin (JWT).
 * Проверяем: доступ, фильтры (status/festival/type_order/email/city), пагинацию, totalNumber,
 * сортировку и устойчивость к кривому orderBy.
 */
class QrOrderGetListApiTest extends TestCase
{
    private const F1 = '11111111-1111-1111-1111-111111111111';
    private const F2 = '22222222-2222-2222-2222-222222222222';

    /** Создать строку qr_orders с дефолтами; created_at можно задать явно для теста сортировки. */
    private function makeOrder(array $overrides, ?Carbon $createdAt = null): QrOrderModel
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
            // created_at не в fillable + автоштамп таймстампов — обновляем напрямую запросом.
            QrOrderModel::whereId($order->id)->update(['created_at' => $createdAt]);
        }

        return $order;
    }

    /** Залить набор из 5 заказов с разными проекциями под фильтры. */
    private function seedOrders(): void
    {
        $this->makeOrder(['email' => 'alice@example.com', 'status' => 'оплачен', 'festival_id' => self::F1, 'type_order' => 'regular', 'city' => 'Москва'], Carbon::parse('2026-06-01 10:00:00'));
        $this->makeOrder(['email' => 'bob@example.com',   'status' => 'создан',  'festival_id' => self::F1, 'type_order' => 'friendly', 'city' => 'Казань'], Carbon::parse('2026-06-02 10:00:00'));
        $this->makeOrder(['email' => 'carol@example.com', 'status' => 'оплачен', 'festival_id' => self::F2, 'type_order' => 'list', 'city' => 'Москва'], Carbon::parse('2026-06-03 10:00:00'));
        $this->makeOrder(['email' => 'dave@test.com',     'status' => 'отменён', 'festival_id' => self::F2, 'type_order' => 'live', 'city' => 'Сочи'], Carbon::parse('2026-06-04 10:00:00'));
        $this->makeOrder(['email' => 'erin@example.com',  'status' => 'оплачен', 'festival_id' => self::F1, 'type_order' => 'regular', 'city' => 'Москва'], Carbon::parse('2026-06-05 10:00:00'));
    }

    private function actingAsAdmin(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']), 'api');
    }

    private function sendGetList(array $body = [])
    {
        return $this->postJson('/api/v1/qrOrder/getList', $body);
    }

    public function test_requires_authentication(): void
    {
        // Без JWT — эндпоинт закрыт (содержит ПДн).
        $this->sendGetList()->assertStatus(401);
    }

    public function test_forbidden_for_non_admin(): void
    {
        // Авторизован, но не админ → 403.
        $this->actingAs(User::factory()->create(['role' => 'guest']), 'api');

        $this->sendGetList()->assertStatus(403);
    }

    public function test_returns_full_list_with_total(): void
    {
        $this->actingAsAdmin();
        $this->seedOrders();

        $response = $this->sendGetList()->assertOk()->assertJson(['success' => true]);

        $response->assertJsonPath('totalNumber.totalCount', 5);
        self::assertCount(5, $response->json('list'));
        // Проекция списка — snake_case, без payload (он только в getItem).
        $response->assertJsonStructure([
            'list' => [['id', 'email', 'status', 'festival_id', 'type_order', 'city', 'phone', 'total_price', 'issued_at', 'created_at']],
        ]);
        self::assertArrayNotHasKey('payload', $response->json('list.0'));
    }

    public function test_filter_by_status(): void
    {
        $this->actingAsAdmin();
        $this->seedOrders();

        $response = $this->sendGetList(['filter' => ['status' => 'оплачен']])->assertOk();

        $response->assertJsonPath('totalNumber.totalCount', 3);
        self::assertCount(3, $response->json('list'));
        foreach ($response->json('list') as $item) {
            self::assertSame('оплачен', $item['status']);
        }
    }

    public function test_filter_by_festival(): void
    {
        $this->actingAsAdmin();
        $this->seedOrders();

        $response = $this->sendGetList(['filter' => ['festival_id' => self::F1]])->assertOk();

        $response->assertJsonPath('totalNumber.totalCount', 3);
        self::assertCount(3, $response->json('list'));
    }

    public function test_filter_by_type_order(): void
    {
        $this->actingAsAdmin();
        $this->seedOrders();

        $response = $this->sendGetList(['filter' => ['type_order' => 'regular']])->assertOk();

        $response->assertJsonPath('totalNumber.totalCount', 2);
        self::assertCount(2, $response->json('list'));
    }

    public function test_filter_by_email_is_partial(): void
    {
        $this->actingAsAdmin();
        $this->seedOrders();

        // LIKE %example.com% → 4 из 5 (dave@test.com не попадает).
        $response = $this->sendGetList(['filter' => ['email' => 'example.com']])->assertOk();

        $response->assertJsonPath('totalNumber.totalCount', 4);
        self::assertCount(4, $response->json('list'));
    }

    public function test_filter_by_city_is_partial(): void
    {
        $this->actingAsAdmin();
        $this->seedOrders();

        $response = $this->sendGetList(['filter' => ['city' => 'Москва']])->assertOk();

        $response->assertJsonPath('totalNumber.totalCount', 3);
    }

    public function test_filters_combine(): void
    {
        $this->actingAsAdmin();
        $this->seedOrders();

        // status=оплачен И festival=F1 → A и E (2).
        $response = $this->sendGetList(['filter' => ['status' => 'оплачен', 'festival_id' => self::F1]])->assertOk();

        $response->assertJsonPath('totalNumber.totalCount', 2);
        self::assertCount(2, $response->json('list'));
    }

    public function test_pagination_returns_page_slice_but_full_total(): void
    {
        $this->actingAsAdmin();
        $this->seedOrders();

        // Страница 1 по 2 элемента: в списке 2, но total — полный (5).
        $page1 = $this->sendGetList(['page' => 1, 'perPage' => 2])->assertOk();
        self::assertCount(2, $page1->json('list'));
        $page1->assertJsonPath('totalNumber.totalCount', 5);

        // Последняя (третья) страница — 1 элемент.
        $page3 = $this->sendGetList(['page' => 3, 'perPage' => 2])->assertOk();
        self::assertCount(1, $page3->json('list'));
        $page3->assertJsonPath('totalNumber.totalCount', 5);
    }

    public function test_order_by_created_at_desc(): void
    {
        $this->actingAsAdmin();
        $this->seedOrders();

        // Сортировка по created_at desc → первым самый свежий (erin, 2026-06-05).
        $response = $this->sendGetList(['orderBy' => ['created_at' => 'desc']])->assertOk();

        self::assertSame('erin@example.com', $response->json('list.0.email'));
    }

    public function test_invalid_order_by_does_not_crash(): void
    {
        $this->actingAsAdmin();
        $this->seedOrders();

        // Кривое направление сортировки не роняет запрос (fallback на Order::none()).
        $response = $this->sendGetList(['orderBy' => ['created_at' => 'вбок']])->assertOk();

        $response->assertJsonPath('totalNumber.totalCount', 5);
    }
}
