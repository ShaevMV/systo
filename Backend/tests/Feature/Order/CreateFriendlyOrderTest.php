<?php

declare(strict_types=1);

namespace Tests\Feature\Order;

use App\Models\Ordering\OrderTicketModel;
use App\Models\User;
use Database\Seeders\OptionTestDataSeeder;
use Database\Seeders\TypesOfPaymentSeeder;
use Database\Seeders\TypeTicketsSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Support\Facades\Bus;
use Shared\Domain\ValueObject\Status;
use Tests\TestCase;
use Tickets\Ticket\Live\Service\CheckLiveTicketService;
use Tickets\Order\OrderTicket\Helpers\FestivalHelper;

/**
 * Feature-тест нового формата `POST /api/v1/order/createFriendly` (v2.6.0, per-guest).
 *
 * Тестируем именно HTTP-границу (как реализовано в проекте: реальная БД `systo_test`
 * через {@see TestCase} + DatabaseSeeder), потому что ядро изменения — приватные хелперы
 * контроллера `distributeManualTotal()` / `toFriendlyLine()`. Их единственный публичный
 * вход — этот эндпоинт, поэтому проверяем поведение через него + читаем сохранённый
 * `order_tickets.guests[]` JSON.
 *
 * Что покрываем (см. ТЗ):
 *  A) распределение ручного итога пушера поровну (остаток первому гостю);
 *     сумма долей строго равна введённой сумме при любом делении;
 *  B) опции сохраняют имя, но цена обнулена (priceSnapshot=0, options_sum=0),
 *     итог строки = доля ручного итога;
 *  C) живые билеты — number из payload, статус LIVE_TICKET_ISSUED;
 *  D) обычные (non-live) — статус PAID.
 *
 * Доменные события (`ProcessCreateTicket`/QR/PDF/письма) глушим через {@see Bus::fake()} —
 * заказ всё равно сохраняется (`createAndSaveForFriendly` пишет в БД до dispatch chain),
 * а тяжёлые/сетевые джобы (генерация QR, sync Baza, почта) не выполняются.
 *
 * Аутентификация — реальный seed-пушер ({@see UserSeeder::ID_FOR_PUSHER_UUID}) через
 * guard `api` (JWT), как требует middleware `role:pusher,pusher_curator`.
 */
class CreateFriendlyOrderTest extends TestCase
{
    private const PAYMENT_ID = TypesOfPaymentSeeder::ID_FOR_FRIENDLY;

    protected function setUp(): void
    {
        parent::setUp();
        // Глушим chain доменных событий (создание билетов, QR, PDF, письма, sync Baza).
        Bus::fake();
    }

    /** Гость в формате v2.6.0 (per-guest). */
    private function guest(
        string $value = 'Гость Тестовый',
        ?string $email = 'guest@example.com',
        ?string $ticketTypeId = null,
        array $options = [],
        ?string $promoCode = null,
        ?int $number = null,
    ): array {
        return [
            'value' => $value,
            'email' => $email,
            'ticket_type_id' => $ticketTypeId ?? TypeTicketsSeeder::ID_FOR_FIRST_WAVE,
            'options' => $options,
            'promo_code' => $promoCode,
            'number' => $number,
        ];
    }

    private function payload(array $guests, float $price): array
    {
        return [
            'email' => 'buyer@example.com',
            'phone' => '+79991234567',
            'city' => 'spb',
            'festival_id' => FestivalHelper::UUID_FESTIVAL,
            'types_of_payment_id' => self::PAYMENT_ID,
            'price' => $price,
            'guests' => $guests,
        ];
    }

    private function actingAsPusher(): User
    {
        /** @var User $pusher */
        $pusher = User::query()->findOrFail(UserSeeder::ID_FOR_PUSHER_UUID);
        $this->actingAs($pusher, 'api');

        return $pusher;
    }

    /**
     * Декодит `order_tickets.guests[]` JSON конкретного заказа.
     *
     * @return array<int, array<string, mixed>>
     */
    private function guestsOf(OrderTicketModel $order): array
    {
        return json_decode($order->guests, true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * POST на createFriendly и возврат именно того заказа, который создал тест.
     *
     * Сидеры ({@see \Database\Seeders\OrderSeeder}) уже наполняют `order_tickets`,
     * поэтому «последний по created_at» не годится — снимаем слепок существующих id
     * до запроса и выбираем единственный новый.
     */
    private function postCreateFriendly(array $payload): OrderTicketModel
    {
        $idsBefore = OrderTicketModel::query()->pluck('id')->all();

        $response = $this->postJson('/api/v1/order/createFriendly', $payload);

        // Контроллер ловит Throwable и отдаёт 200 {success:false}. Если так — показываем
        // сообщение, чтобы тест не «зеленел» на скрытой ошибке и было видно причину.
        if ($response->json('success') !== true) {
            self::fail('createFriendly вернул ошибку: ' . (string) $response->json('message'));
        }

        /** @var OrderTicketModel|null $created */
        $created = OrderTicketModel::query()->whereNotIn('id', $idsBefore)->first();
        self::assertNotNull($created, 'Заказ Friendly не был сохранён в БД');

        return $created;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // A) Распределение ручного итога
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function distributes_manual_total_equally_9000_by_3(): void
    {
        $this->actingAsPusher();

        $order = $this->postCreateFriendly($this->payload([
            $this->guest(value: 'Гость 1'),
            $this->guest(value: 'Гость 2'),
            $this->guest(value: 'Гость 3'),
        ], price: 9000));

        $totals = array_map(
            static fn (array $g) => $g['price_snapshot']['total'],
            $this->guestsOf($order),
        );

        self::assertSame([3000, 3000, 3000], $totals);
        self::assertSame(9000, array_sum($totals));
    }

    /** @test */
    public function distributes_manual_total_with_remainder_to_first_guest_1000_by_3(): void
    {
        $this->actingAsPusher();

        $order = $this->postCreateFriendly($this->payload([
            $this->guest(value: 'Гость 1'),
            $this->guest(value: 'Гость 2'),
            $this->guest(value: 'Гость 3'),
        ], price: 1000));

        $totals = array_map(
            static fn (array $g) => $g['price_snapshot']['total'],
            $this->guestsOf($order),
        );

        // Остаток (1000 - 999) = 1₽ добавляется первому гостю.
        self::assertSame([334, 333, 333], $totals);
        self::assertSame(1000, array_sum($totals));
    }

    /** @test */
    public function single_guest_gets_full_manual_total(): void
    {
        $this->actingAsPusher();

        $order = $this->postCreateFriendly($this->payload([
            $this->guest(),
        ], price: 5000));

        $guests = $this->guestsOf($order);

        self::assertCount(1, $guests);
        self::assertSame(5000, $guests[0]['price_snapshot']['total']);
    }

    /**
     * Инвариант денег: при любом делении сумма долей == введённый итог пушера.
     *
     * @dataProvider tricky_division_provider
     * @test
     */
    public function sum_of_shares_always_equals_manual_total(int $total, int $guestCount): void
    {
        $this->actingAsPusher();

        $guests = [];
        for ($i = 0; $i < $guestCount; $i++) {
            $guests[] = $this->guest(value: 'Гость ' . $i);
        }

        $order = $this->postCreateFriendly($this->payload($guests, price: (float) $total));
        $saved = $this->guestsOf($order);

        $sum = array_sum(array_map(static fn (array $g) => $g['price_snapshot']['total'], $saved));

        self::assertSame($total, $sum, "Сумма долей должна равняться $total при $guestCount гостях");
        self::assertCount($guestCount, $saved);
    }

    public static function tricky_division_provider(): array
    {
        return [
            '7000 / 3' => [7000, 3],
            '10000 / 7' => [10000, 7],
            '1 / 2' => [1, 2],
            '100 / 6' => [100, 6],
            '4999 / 5' => [4999, 5],
            '0 / 3' => [0, 3],
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // B) Опции обнуляются по цене
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function options_keep_name_but_zero_price_and_line_total_equals_share(): void
    {
        $this->actingAsPusher();

        // FIRST_WAVE поддерживает опцию «Саженец» (500₽) — но в Friendly её цена обнуляется.
        $order = $this->postCreateFriendly($this->payload([
            $this->guest(options: [
                ['option_id' => OptionTestDataSeeder::ID_OPTION_SAPLING, 'qty' => 1],
            ]),
        ], price: 4200));

        $line = $this->guestsOf($order)[0];

        // Имя опции сохранено
        self::assertCount(1, $line['options']);
        self::assertSame('Саженец', $line['options'][0]['name']);
        // Цена опции обнулена
        self::assertSame(0, $line['options'][0]['price']);

        // В снимке цены строки опции не учитываются
        self::assertSame(0, $line['price_snapshot']['options_sum']);
        self::assertSame(0, $line['price_snapshot']['discount']);
        // База строки = доля ручного итога (один гость → весь итог)
        self::assertSame(4200, $line['price_snapshot']['base_price']);
        // Итог строки = доля ручного итога, опции не добавляют сверху
        self::assertSame(4200, $line['price_snapshot']['total']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // C) Живые билеты
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function live_ticket_number_from_payload_and_status_is_live_ticket_issued(): void
    {
        $this->actingAsPusher();

        // Проверка уникальности live-номера ходит в БД Baza (`mysqlBaza` connection),
        // недоступную в тест-окружении. Мокаем сервис — номер «свободен», чтобы
        // изолировать per-guest логику контроллера от внешней инфраструктуры.
        $this->mock(CheckLiveTicketService::class, static function ($mock) {
            $mock->shouldReceive('checkLiveNumber')->andReturn(false);
        });

        $order = $this->postCreateFriendly($this->payload([
            $this->guest(
                ticketTypeId: TypeTicketsSeeder::ID_LIVE_FOR_NEXT_FESTIVAL,
                number: 777,
            ),
        ], price: 3800));

        $guests = $this->guestsOf($order);

        self::assertSame(Status::LIVE_TICKET_ISSUED, $order->status);
        self::assertSame(777, $guests[0]['number']);
        self::assertTrue($guests[0]['is_live_ticket']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // D) Обычные (non-live)
    // ─────────────────────────────────────────────────────────────────────────

    /** @test */
    public function non_live_order_status_is_paid_and_number_is_null(): void
    {
        $this->actingAsPusher();

        $order = $this->postCreateFriendly($this->payload([
            $this->guest(),
        ], price: 4200));

        $guests = $this->guestsOf($order);

        self::assertSame(Status::PAID, $order->status);
        self::assertNull($guests[0]['number']);
        self::assertFalse($guests[0]['is_live_ticket']);
    }

    /** @test */
    public function payment_type_is_hardcoded_for_friendly(): void
    {
        $this->actingAsPusher();

        $order = $this->postCreateFriendly($this->payload([
            $this->guest(),
        ], price: 4200));

        self::assertSame(self::PAYMENT_ID, $order->types_of_payment_id);
    }
}
