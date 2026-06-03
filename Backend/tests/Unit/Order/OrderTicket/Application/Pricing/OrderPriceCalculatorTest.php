<?php

declare(strict_types=1);

namespace Tests\Unit\Order\OrderTicket\Application\Pricing;

use Carbon\Carbon;
use Database\Seeders\OptionTestDataSeeder;
use Database\Seeders\PromoCodSeeder;
use Database\Seeders\TypeTicketsSeeder;
use DomainException;
use Illuminate\Support\Facades\DB;
use Shared\Domain\ValueObject\Uuid;
use Tests\TestCase;
use Tickets\Order\OrderTicket\Application\Pricing\Dto\RawGuestInput;
use Tickets\Order\OrderTicket\Application\Pricing\Dto\RawGuestOptionInput;
use Tickets\Order\OrderTicket\Application\Pricing\OrderPriceCalculator;
use Tickets\Order\OrderTicket\Helpers\FestivalHelper;

/**
 * Integration-тест для {@see OrderPriceCalculator}.
 *
 * Запускается через `Tests\TestCase` (RefreshDatabase + DatabaseSeeder), потому что
 * Calculator зависит от final-классов `GetTicketType` и `IsCorrectPromoCode` — мокать
 * Mockery их не может. Контейнер резолвит реальный Calculator со всеми зависимостями,
 * а данные подаются через стандартные сидеры + дополнительный процентный промокод в setUp().
 *
 * Сидеры (см. DatabaseSeeder):
 *  - ID_FOR_FIRST_WAVE (Оргвзнос 3800₽ базово, текущая волна 4200₽)
 *  - ID_FOR_REGIONS    (Оргвзнос для регионов 3600₽ базово, текущая 4000₽)
 *  - ID_LIVE_FOR_NEXT_FESTIVAL (Живой билет 3800₽, is_live_ticket=true)
 *  - ID_OPTION_SAPLING       (Саженец 500₽)
 *  - ID_OPTION_PRINTED_TICKET (Печатный билет 200₽, привязан только к FIRST_WAVE)
 *  - PromoCodSeeder::SYSTO20 (600₽ фикс, привязан к ticket_type_id = ID_FOR_WAVE)
 *  - PromoCodSeeder::illunimiscata (500₽ фикс, без привязки к ticket_type)
 *  - Тестовый процентный промокод PCT10 (10%, привязан к FIRST_WAVE) — создаём в setUp
 *
 * См. `.claude/specs/order-format-architecture.md` §4.2, §7.
 */
class OrderPriceCalculatorTest extends TestCase
{
    private const PCT10_PROMO_ID = 'cccccccc-cccc-cccc-cccc-cccccccccccc';
    private const PCT10_PROMO_NAME = 'PCT10';

    private OrderPriceCalculator $calculator;
    private Uuid $festivalId;
    private Uuid $ticketTypeOrgvznos;
    private Uuid $ticketTypeLive;
    private Uuid $optionSapling;
    private Uuid $optionPrintedTicket;

    protected function setUp(): void
    {
        parent::setUp();

        // Дополнительный процентный промокод — у PromoCodSeeder только фиксированные.
        // Привязан к ticket_type ID_FOR_FIRST_WAVE — это поведение поиска `IsCorrectPromoCode`
        // (фильтр по ticket_type_id в репозитории).
        DB::table('promo_code')->insert([
            'id' => self::PCT10_PROMO_ID,
            'name' => self::PCT10_PROMO_NAME,
            'discount' => 10,
            'is_percent' => true,
            'active' => true,
            'ticket_type_id' => TypeTicketsSeeder::ID_FOR_FIRST_WAVE,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        $this->calculator = $this->app->make(OrderPriceCalculator::class);
        $this->festivalId = new Uuid(FestivalHelper::UUID_FESTIVAL);
        $this->ticketTypeOrgvznos = new Uuid(TypeTicketsSeeder::ID_FOR_FIRST_WAVE);
        $this->ticketTypeLive = new Uuid(TypeTicketsSeeder::ID_LIVE_FOR_NEXT_FESTIVAL);
        $this->optionSapling = new Uuid(OptionTestDataSeeder::ID_OPTION_SAPLING);
        $this->optionPrintedTicket = new Uuid(OptionTestDataSeeder::ID_OPTION_PRINTED_TICKET);
    }

    /** @test */
    public function calculates_simple_guest_without_options_or_promo(): void
    {
        $lines = $this->calculator->calculateLines($this->festivalId, [
            $this->makeGuest($this->ticketTypeOrgvznos),
        ]);

        self::assertCount(1, $lines);
        $line = $lines[0];

        // Базовая цена FIRST_WAVE с активной волной — 4200₽ (PRICE_FOR_SECOND_WAVE)
        self::assertSame(4200, $line->price->basePrice->amount());
        self::assertSame(0, $line->price->optionsSum->amount());
        self::assertSame(0, $line->price->discount->amount());
        self::assertSame(4200, $line->total()->amount());
        self::assertFalse($line->isLive());
    }

    /** @test */
    public function calculates_two_guests_doubles_total(): void
    {
        $lines = $this->calculator->calculateLines($this->festivalId, [
            $this->makeGuest($this->ticketTypeOrgvznos),
            $this->makeGuest($this->ticketTypeOrgvznos),
        ]);

        self::assertCount(2, $lines);
        self::assertSame(4200, $lines[0]->total()->amount());
        self::assertSame(4200, $lines[1]->total()->amount());
    }

    /** @test */
    public function expands_option_qty_into_repeated_snapshots(): void
    {
        $lines = $this->calculator->calculateLines($this->festivalId, [
            $this->makeGuest($this->ticketTypeOrgvznos, options: [
                new RawGuestOptionInput($this->optionSapling, 2),
            ]),
        ]);

        $line = $lines[0];

        // qty=2 → 2 снимка по 500₽ = 1000₽
        self::assertCount(2, $line->options);
        self::assertSame(500, $line->options[0]->priceSnapshot->amount());
        self::assertSame('Саженец', $line->options[0]->nameSnapshot);

        self::assertSame(4200, $line->price->basePrice->amount());
        self::assertSame(1000, $line->price->optionsSum->amount());
        self::assertSame(5200, $line->total()->amount());
    }

    /** @test */
    public function combines_multiple_distinct_options_in_options_sum(): void
    {
        $lines = $this->calculator->calculateLines($this->festivalId, [
            $this->makeGuest($this->ticketTypeOrgvznos, options: [
                new RawGuestOptionInput($this->optionSapling, 1),
                new RawGuestOptionInput($this->optionPrintedTicket, 1),
            ]),
        ]);

        $line = $lines[0];

        self::assertCount(2, $line->options);
        // 4200 + 500 + 200 = 4900
        self::assertSame(700, $line->price->optionsSum->amount());
        self::assertSame(4900, $line->total()->amount());
    }

    /** @test */
    public function applies_fixed_promocode_subtracted_from_total(): void
    {
        // SYSTO20 — фиксированная скидка 600₽, привязана к ID_FOR_WAVE (ticket_type_price.id);
        // ↳ репозиторий ищет промокод по ticket_type_price_id = ticket_type ID_FOR_FIRST_WAVE — связка через волну
        $lines = $this->calculator->calculateLines($this->festivalId, [
            $this->makeGuest($this->ticketTypeOrgvznos, promoCode: PromoCodSeeder::NAME_FOR_SYSTO),
        ]);

        $line = $lines[0];

        // 4200 базовая - 600 скидка = 3600
        self::assertSame(4200, $line->price->basePrice->amount());
        self::assertSame(600, $line->price->discount->amount());
        self::assertSame(3600, $line->total()->amount());
    }

    /** @test */
    public function applies_percent_promocode_only_to_base_not_options(): void
    {
        // ЗАФИКСИРОВАННОЕ ПРАВИЛО (встреча 2026-05-30): процент применяется только к базе билета,
        // НЕ к base + options. Это критичный invariant — если ошибиться, опции будут «бесплатными»
        // в части стоимости и пользователи получат непредвиденную скидку.
        $lines = $this->calculator->calculateLines($this->festivalId, [
            $this->makeGuest(
                $this->ticketTypeOrgvznos,
                options: [new RawGuestOptionInput($this->optionSapling, 1)],
                promoCode: self::PCT10_PROMO_NAME,
            ),
        ]);

        $line = $lines[0];

        // base=4200, options=500, discount = 10% от base = 420
        // total = 4200 + 500 - 420 = 4280
        self::assertSame(4200, $line->price->basePrice->amount());
        self::assertSame(500, $line->price->optionsSum->amount());
        self::assertSame(420, $line->price->discount->amount());
        self::assertSame(4280, $line->total()->amount());
    }

    /** @test */
    public function throws_when_mixing_live_and_non_live_ticket_types(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('нельзя смешивать живые и обычные билеты');

        $this->calculator->calculateLines($this->festivalId, [
            $this->makeGuest($this->ticketTypeOrgvznos),   // non-live
            $this->makeGuest($this->ticketTypeLive),       // live
        ]);
    }

    /** @test */
    public function throws_when_option_not_active_for_ticket_type(): void
    {
        // Опция «Печатный билет» привязана ТОЛЬКО к FIRST_WAVE, не к REGIONS.
        // Попытка купить её на REGIONS должна провалиться — защита от подмены option_id в payload.
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('недоступна для типа билета');

        $this->calculator->calculateLines($this->festivalId, [
            $this->makeGuest(
                new Uuid(TypeTicketsSeeder::ID_FOR_REGIONS),
                options: [new RawGuestOptionInput($this->optionPrintedTicket, 1)],
            ),
        ]);
    }

    /** @test */
    public function throws_when_guests_array_is_empty(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('at least one guest');

        $this->calculator->calculateLines($this->festivalId, []);
    }

    /** @test */
    public function throws_when_guests_array_exceeds_max_per_order(): void
    {
        // Защита от DoS: payload с 1000 гостей × N опций исчерпает память.
        $tooMany = array_fill(0, OrderPriceCalculator::MAX_GUESTS_PER_ORDER + 1, $this->makeGuest($this->ticketTypeOrgvznos));

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('нельзя оформить более ' . OrderPriceCalculator::MAX_GUESTS_PER_ORDER);

        $this->calculator->calculateLines($this->festivalId, $tooMany);
    }

    /** @test */
    public function throws_when_ticket_type_belongs_to_different_festival(): void
    {
        // Атакующий передаёт ticket_type_id из ФестиваляB в заказ на ФестивальA.
        // Без проверки festival_id Calculator оформил бы билет ФестиваляB.
        // ID_FOR_NEXT_FESTIVAL привязан к UUID_SECOND_FESTIVAL, а $this->festivalId = UUID_FESTIVAL.
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('не принадлежит фестивалю');

        $this->calculator->calculateLines($this->festivalId, [
            $this->makeGuest(new Uuid(TypeTicketsSeeder::ID_FOR_NEXT_FESTIVAL)),
        ]);
    }

    /** @test */
    public function discount_clamps_to_zero_when_exceeds_base_plus_options(): void
    {
        // Большая фиксированная скидка > base+options. Money::subtract клампит к 0 — не уходит в минус.
        // Создаём промокод 99999₽ для FIRST_WAVE.
        DB::table('promo_code')->insert([
            'id' => 'dddddddd-dddd-dddd-dddd-dddddddddddd',
            'name' => 'HUGE_DISCOUNT',
            'discount' => 99999,
            'is_percent' => false,
            'active' => true,
            'ticket_type_id' => TypeTicketsSeeder::ID_FOR_FIRST_WAVE,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        $lines = $this->calculator->calculateLines($this->festivalId, [
            $this->makeGuest($this->ticketTypeOrgvznos, promoCode: 'HUGE_DISCOUNT'),
        ]);

        self::assertSame(0, $lines[0]->total()->amount());
    }

    /** @test */
    public function propagates_is_live_ticket_flag_to_order_guest_line(): void
    {
        $lines = $this->calculator->calculateLines($this->festivalId, [
            $this->makeGuest($this->ticketTypeLive),
        ]);

        // Флаг приходит из ticket_type.is_live_ticket в LiveDto и пропагируется в Domain VO
        // → дальше OrderTicket агрегат знает что это live-флоу (PAID_FOR_LIVE → LIVE_TICKET_ISSUED).
        self::assertTrue($lines[0]->isLive());
    }

    /** @test */
    public function fills_required_fields_in_order_guest_line(): void
    {
        $lines = $this->calculator->calculateLines($this->festivalId, [
            $this->makeGuest($this->ticketTypeOrgvznos, value: 'Иванов Иван', email: 'ivan@example.com'),
        ]);

        $line = $lines[0];

        self::assertSame('Иванов Иван', $line->value);
        self::assertSame('ivan@example.com', $line->email);
        self::assertTrue($line->ticketTypeId->equals($this->ticketTypeOrgvznos));
        self::assertTrue($line->festivalId->equals($this->festivalId));
        self::assertNull($line->number);  // живой номер ставится позже в toLiveIssued
    }

    /** @test */
    public function generates_unique_ids_for_each_line(): void
    {
        // Каждая строка должна иметь уникальный id (Uuid::random()) — иначе ID коллизия в БД.
        $lines = $this->calculator->calculateLines($this->festivalId, [
            $this->makeGuest($this->ticketTypeOrgvznos),
            $this->makeGuest($this->ticketTypeOrgvznos),
            $this->makeGuest($this->ticketTypeOrgvznos),
        ]);

        $ids = array_map(static fn ($line) => $line->id->value(), $lines);
        self::assertCount(3, array_unique($ids));
    }

    /**
     * @param  RawGuestOptionInput[]  $options
     */
    private function makeGuest(
        Uuid $ticketTypeId,
        array $options = [],
        ?string $promoCode = null,
        string $value = 'Тестовый Гость',
        string $email = 'guest@example.com',
    ): RawGuestInput {
        return new RawGuestInput(
            value: $value,
            email: $email,
            ticketTypeId: $ticketTypeId,
            options: $options,
            promoCode: $promoCode,
        );
    }
}
