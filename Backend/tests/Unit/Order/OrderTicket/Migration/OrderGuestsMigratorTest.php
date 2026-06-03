<?php

declare(strict_types=1);

namespace Tests\Unit\Order\OrderTicket\Migration;

use Illuminate\Database\ConnectionInterface;
use Mockery;
use PHPUnit\Framework\TestCase;
use Tickets\Order\OrderTicket\Migration\OrderGuestsMigrator;

/**
 * Unit-тесты на чистую функцию {@see OrderGuestsMigrator::transformOrderGuests()}.
 *
 * Тестируем без БД — мокаем connection (он не используется внутри transformOrderGuests).
 * Логика migrate() с обходом chunk() покрывается integration-тестами на staging.
 */
class OrderGuestsMigratorTest extends TestCase
{
    private const TICKET_TYPE_ORG = 'a1111111-1111-1111-1111-111111111111';
    private const TICKET_TYPE_LIVE = 'a2222222-2222-2222-2222-222222222222';
    private const PROMO_CODE = 'SYSTO20';

    private OrderGuestsMigrator $migrator;

    protected function setUp(): void
    {
        parent::setUp();

        $connection = Mockery::mock(ConnectionInterface::class);
        $this->migrator = new OrderGuestsMigrator($connection);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function transforms_single_guest_with_no_discount(): void
    {
        $result = $this->migrator->transformOrderGuests(
            guests: [$this->legacyGuest('Иван')],
            ticketTypeId: self::TICKET_TYPE_ORG,
            promoCode: null,
            price: 4200.0,
            discount: 0.0,
            liveByTicketType: [self::TICKET_TYPE_ORG => false],
        );

        self::assertTrue($result['migrated']);
        self::assertFalse($result['emptyGuests']);
        self::assertSame(4200.0, $result['totalBefore']);
        self::assertSame(4200.0, $result['totalAfter']);

        $guest = $result['guests'][0];
        self::assertSame(self::TICKET_TYPE_ORG, $guest['ticket_type_id']);
        self::assertSame([], $guest['options']);
        self::assertNull($guest['promo_code']);
        self::assertFalse($guest['is_live_ticket']);

        self::assertSame(4200, $guest['price_snapshot']['base_price']);
        self::assertSame(0, $guest['price_snapshot']['options_sum']);
        self::assertSame(0, $guest['price_snapshot']['discount']);
        self::assertSame(4200, $guest['price_snapshot']['total']);
    }

    /** @test */
    public function distributes_price_equally_across_two_guests(): void
    {
        // price 8400, discount 600 → totalPerGuest=(8400-600)/2=3900, discountPerGuest=300, base=4200
        $result = $this->migrator->transformOrderGuests(
            guests: [$this->legacyGuest('Иван'), $this->legacyGuest('Мария')],
            ticketTypeId: self::TICKET_TYPE_ORG,
            promoCode: self::PROMO_CODE,
            price: 8400.0,
            discount: 600.0,
            liveByTicketType: [self::TICKET_TYPE_ORG => false],
        );

        self::assertTrue($result['migrated']);
        self::assertSame(7800.0, $result['totalBefore']);  // 8400 - 600
        self::assertSame(7800.0, $result['totalAfter']);   // 3900 × 2

        foreach ($result['guests'] as $guest) {
            self::assertSame(4200, $guest['price_snapshot']['base_price']);
            self::assertSame(300, $guest['price_snapshot']['discount']);
            self::assertSame(3900, $guest['price_snapshot']['total']);
            self::assertSame(self::PROMO_CODE, $guest['promo_code']);
        }
    }

    /** @test */
    public function rounding_split_across_three_guests_within_tolerance(): void
    {
        // price 10000, discount 0, count 3 → totalPerGuest = round(10000/3) = 3333
        // Σ total после = 3333 × 3 = 9999, расхождение -1 ₽ (≤ count-1 = 2)
        $result = $this->migrator->transformOrderGuests(
            guests: [$this->legacyGuest('A'), $this->legacyGuest('B'), $this->legacyGuest('C')],
            ticketTypeId: self::TICKET_TYPE_ORG,
            promoCode: null,
            price: 10000.0,
            discount: 0.0,
            liveByTicketType: [self::TICKET_TYPE_ORG => false],
        );

        self::assertSame(10000.0, $result['totalBefore']);
        self::assertSame(9999.0, $result['totalAfter']);
        self::assertEqualsWithDelta(1.0, abs($result['totalBefore'] - $result['totalAfter']), 0.01);

        foreach ($result['guests'] as $guest) {
            self::assertSame(3333, $guest['price_snapshot']['total']);
        }
    }

    /** @test */
    public function preserves_existing_guest_fields_and_adds_new_ones(): void
    {
        $legacyGuest = [
            'id' => '550e8400-e29b-41d4-a716-446655440000',
            'value' => 'Иван Петров',
            'email' => 'ivan@example.com',
            'number' => 42,
            'festival_id' => '660e8400-e29b-41d4-a716-446655440001',
        ];

        $result = $this->migrator->transformOrderGuests(
            guests: [$legacyGuest],
            ticketTypeId: self::TICKET_TYPE_ORG,
            promoCode: null,
            price: 4200.0,
            discount: 0.0,
            liveByTicketType: [self::TICKET_TYPE_ORG => false],
        );

        $guest = $result['guests'][0];

        // Старые поля сохранены
        self::assertSame('550e8400-e29b-41d4-a716-446655440000', $guest['id']);
        self::assertSame('Иван Петров', $guest['value']);
        self::assertSame('ivan@example.com', $guest['email']);
        self::assertSame(42, $guest['number']);
        self::assertSame('660e8400-e29b-41d4-a716-446655440001', $guest['festival_id']);

        // Новые поля добавлены
        self::assertArrayHasKey('ticket_type_id', $guest);
        self::assertArrayHasKey('options', $guest);
        self::assertArrayHasKey('promo_code', $guest);
        self::assertArrayHasKey('price_snapshot', $guest);
        self::assertArrayHasKey('is_live_ticket', $guest);
    }

    /** @test */
    public function is_idempotent_skips_already_migrated_orders(): void
    {
        // Идемпотентность: повторный прогон не должен ничего менять.
        $alreadyMigratedGuest = array_merge($this->legacyGuest('Иван'), [
            'ticket_type_id' => self::TICKET_TYPE_ORG,
            'options' => [],
            'promo_code' => null,
            'price_snapshot' => [
                'base_price' => 4200,
                'options_sum' => 0,
                'discount' => 0,
                'total' => 4200,
            ],
            'is_live_ticket' => false,
        ]);

        $result = $this->migrator->transformOrderGuests(
            guests: [$alreadyMigratedGuest],
            ticketTypeId: self::TICKET_TYPE_ORG,
            promoCode: null,
            price: 4200.0,
            discount: 0.0,
            liveByTicketType: [self::TICKET_TYPE_ORG => false],
        );

        self::assertFalse($result['migrated']);
        self::assertFalse($result['emptyGuests']);
        // Гости остались без изменений (тот же массив)
        self::assertEquals($alreadyMigratedGuest, $result['guests'][0]);
    }

    /** @test */
    public function handles_empty_guests_array(): void
    {
        // Edge: заказ с пустым массивом гостей (не должно происходить, но защищаемся).
        $result = $this->migrator->transformOrderGuests(
            guests: [],
            ticketTypeId: self::TICKET_TYPE_ORG,
            promoCode: null,
            price: 0.0,
            discount: 0.0,
            liveByTicketType: [],
        );

        self::assertFalse($result['migrated']);
        self::assertTrue($result['emptyGuests']);
        self::assertSame([], $result['guests']);
    }

    /** @test */
    public function propagates_is_live_ticket_flag_from_ticket_type(): void
    {
        $result = $this->migrator->transformOrderGuests(
            guests: [$this->legacyGuest('Иван')],
            ticketTypeId: self::TICKET_TYPE_LIVE,
            promoCode: null,
            price: 3800.0,
            discount: 0.0,
            liveByTicketType: [
                self::TICKET_TYPE_ORG => false,
                self::TICKET_TYPE_LIVE => true,
            ],
        );

        self::assertTrue($result['guests'][0]['is_live_ticket']);
    }

    /** @test */
    public function throws_when_ticket_type_unknown(): void
    {
        // Раньше silent fallback на is_live_ticket=false скрывал orphan FK / удалённые типы.
        // Теперь явно репортим — заказ попадёт в errors отчёта и команда вернёт FAILURE.
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('ticket_type ffffffff-ffff-ffff-ffff-ffffffffffff не найден');

        $this->migrator->transformOrderGuests(
            guests: [$this->legacyGuest('Иван')],
            ticketTypeId: 'ffffffff-ffff-ffff-ffff-ffffffffffff',
            promoCode: null,
            price: 4200.0,
            discount: 0.0,
            liveByTicketType: [],  // пустой map
        );
    }

    /** @test */
    public function throws_when_discount_exceeds_price(): void
    {
        // discount > price → отрицательный total. Money не может быть отрицательным,
        // поэтому отвергаем заранее с понятным сообщением (вместо ошибки в Domain VO).
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('discount > price');

        $this->migrator->transformOrderGuests(
            guests: [$this->legacyGuest('Иван')],
            ticketTypeId: self::TICKET_TYPE_ORG,
            promoCode: null,
            price: 100.0,
            discount: 500.0,  // больше чем price
            liveByTicketType: [self::TICKET_TYPE_ORG => false],
        );
    }

    /** @test */
    public function uses_bankers_rounding_for_half_values(): void
    {
        // Banker's (half-even) для совпадения с Money::fromFloat(). На граничном 0.5:
        // обычный round(0.5) = 1 (half-away-from-zero),
        // banker's round(0.5, 0, HALF_EVEN) = 0 (округляем к чётному).
        // Проверяем на price=1, count=2 → totalPerGuest = round(0.5) → ожидаем 0 (banker's).
        $result = $this->migrator->transformOrderGuests(
            guests: [$this->legacyGuest('A'), $this->legacyGuest('B')],
            ticketTypeId: self::TICKET_TYPE_ORG,
            promoCode: null,
            price: 1.0,
            discount: 0.0,
            liveByTicketType: [self::TICKET_TYPE_ORG => false],
        );

        // 1/2 = 0.5 → banker's → 0 (round to even). 0+0 = 0.
        foreach ($result['guests'] as $guest) {
            self::assertSame(0, $guest['price_snapshot']['total']);
        }
        self::assertSame(0.0, $result['totalAfter']);
    }

    /** @test */
    public function migrates_list_order_with_null_ticket_type_id(): void
    {
        // Заказы-списки (curator_id != null) имеют ticket_type_id = null — это норма.
        // OrderGuestLine принимает nullable ticket_type_id.
        $result = $this->migrator->transformOrderGuests(
            guests: [$this->legacyGuest('Иван')],
            ticketTypeId: null,
            promoCode: null,
            price: 0.0,
            discount: 0.0,
            liveByTicketType: [],
        );

        self::assertTrue($result['migrated']);
        self::assertNull($result['guests'][0]['ticket_type_id']);
        self::assertFalse($result['guests'][0]['is_live_ticket']);
        self::assertSame(0, $result['guests'][0]['price_snapshot']['total']);
    }

    /** @test */
    public function options_sum_is_always_zero_for_legacy_orders(): void
    {
        // Опций в v2.5 не было — все мигрированные guests должны иметь options=[] и options_sum=0.
        $result = $this->migrator->transformOrderGuests(
            guests: [$this->legacyGuest('Иван'), $this->legacyGuest('Мария')],
            ticketTypeId: self::TICKET_TYPE_ORG,
            promoCode: null,
            price: 8400.0,
            discount: 0.0,
            liveByTicketType: [self::TICKET_TYPE_ORG => false],
        );

        foreach ($result['guests'] as $guest) {
            self::assertSame([], $guest['options']);
            self::assertSame(0, $guest['price_snapshot']['options_sum']);
        }
    }

    /** @test */
    public function discount_distributes_evenly_when_price_is_zero(): void
    {
        // Edge: price=0, discount=0 — список без цены. snapshot должен быть {0,0,0,0}.
        $result = $this->migrator->transformOrderGuests(
            guests: [$this->legacyGuest('A'), $this->legacyGuest('B')],
            ticketTypeId: null,
            promoCode: null,
            price: 0.0,
            discount: 0.0,
            liveByTicketType: [],
        );

        foreach ($result['guests'] as $guest) {
            self::assertSame(0, $guest['price_snapshot']['base_price']);
            self::assertSame(0, $guest['price_snapshot']['options_sum']);
            self::assertSame(0, $guest['price_snapshot']['discount']);
            self::assertSame(0, $guest['price_snapshot']['total']);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function legacyGuest(string $name): array
    {
        return [
            'id' => sprintf('550e8400-e29b-41d4-a716-%012d', mt_rand(1, 999999)),
            'value' => $name,
            'email' => sprintf('%s@example.com', strtolower($name)),
            'number' => null,
            'festival_id' => '660e8400-e29b-41d4-a716-446655440001',
        ];
    }
}
