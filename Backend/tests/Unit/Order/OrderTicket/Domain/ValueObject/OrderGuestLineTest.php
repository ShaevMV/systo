<?php

declare(strict_types=1);

namespace Tests\Unit\Order\OrderTicket\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Shared\Domain\ValueObject\Money;
use Shared\Domain\ValueObject\Uuid;
use Tickets\Order\OrderTicket\Domain\ValueObject\MoneySnapshot;
use Tickets\Order\OrderTicket\Domain\ValueObject\OrderGuestLine;
use Tickets\Order\OrderTicket\Domain\ValueObject\OrderGuestOption;

class OrderGuestLineTest extends TestCase
{
    private const GUEST_ID = 'aaaaaaaa-1111-1111-1111-111111111111';
    private const FESTIVAL_ID = 'bbbbbbbb-2222-2222-2222-222222222222';
    private const TICKET_TYPE_ID_REGULAR = 'cccccccc-3333-3333-3333-333333333333';
    private const SAPLING_OPTION_ID = 'a1111111-1111-1111-1111-111111111111';

    /**
     * Хелпер — собирает простую строку без опций и скидки, base 4200.
     */
    private function makeRegularLine(): OrderGuestLine
    {
        return new OrderGuestLine(
            id: new Uuid(self::GUEST_ID),
            value: 'Иван Иванов',
            email: 'ivan@example.com',
            number: null,
            festivalId: new Uuid(self::FESTIVAL_ID),
            ticketTypeId: new Uuid(self::TICKET_TYPE_ID_REGULAR),
            options: [],
            promoCode: null,
            price: new MoneySnapshot(new Money(4200), Money::zero(), Money::zero()),
        );
    }

    public function test_constructor_stores_all_fields(): void
    {
        $line = $this->makeRegularLine();

        self::assertSame(self::GUEST_ID, $line->id->value());
        self::assertSame('Иван Иванов', $line->value);
        self::assertSame('ivan@example.com', $line->email);
        self::assertNull($line->number);
        self::assertSame(self::FESTIVAL_ID, $line->festivalId->value());
        self::assertSame(self::TICKET_TYPE_ID_REGULAR, $line->ticketTypeId?->value());
        self::assertSame([], $line->options);
        self::assertNull($line->promoCode);
        self::assertFalse($line->isLiveTicket);
    }

    public function test_total_delegates_to_price_snapshot(): void
    {
        $line = new OrderGuestLine(
            id: new Uuid(self::GUEST_ID),
            value: 'Гость',
            email: null,
            number: null,
            festivalId: new Uuid(self::FESTIVAL_ID),
            ticketTypeId: new Uuid(self::TICKET_TYPE_ID_REGULAR),
            options: [
                new OrderGuestOption(new Uuid(self::SAPLING_OPTION_ID), 'Саженец', new Money(500)),
            ],
            promoCode: 'PROMO10',
            price: new MoneySnapshot(new Money(4200), new Money(500), new Money(200)),
        );

        // 4200 + 500 - 200 = 4500
        self::assertSame(4500, $line->total()->amount());
    }

    public function test_is_child_true_for_child_ticket_type_id(): void
    {
        $line = new OrderGuestLine(
            id: new Uuid(self::GUEST_ID),
            value: 'Дитя',
            email: null,
            number: null,
            festivalId: new Uuid(self::FESTIVAL_ID),
            ticketTypeId: new Uuid(OrderGuestLine::CHILD_TICKET_TYPE_ID),
            options: [],
            promoCode: null,
            price: MoneySnapshot::zero(),
        );

        self::assertTrue($line->isChild());
    }

    public function test_is_child_false_for_other_ticket_type_id(): void
    {
        $line = $this->makeRegularLine();

        self::assertFalse($line->isChild());
    }

    public function test_is_child_false_when_ticket_type_id_is_null(): void
    {
        // list-orders: ticketTypeId = null
        $line = new OrderGuestLine(
            id: new Uuid(self::GUEST_ID),
            value: 'Гость списка',
            email: null,
            number: null,
            festivalId: new Uuid(self::FESTIVAL_ID),
            ticketTypeId: null,
            options: [],
            promoCode: null,
            price: MoneySnapshot::zero(),
        );

        self::assertFalse($line->isChild());
    }

    public function test_is_live_reflects_constructor_flag(): void
    {
        $live = new OrderGuestLine(
            id: new Uuid(self::GUEST_ID),
            value: 'Иван',
            email: null,
            number: 42,
            festivalId: new Uuid(self::FESTIVAL_ID),
            ticketTypeId: new Uuid(self::TICKET_TYPE_ID_REGULAR),
            options: [],
            promoCode: null,
            price: new MoneySnapshot(new Money(3800), Money::zero(), Money::zero()),
            isLiveTicket: true,
        );

        $regular = $this->makeRegularLine();

        self::assertTrue($live->isLive());
        self::assertFalse($regular->isLive());
    }

    public function test_with_regenerated_id_returns_new_object_with_new_uuid(): void
    {
        $line = $this->makeRegularLine();
        $regen = $line->withRegeneratedId();

        self::assertNotSame($line, $regen);
        self::assertNotSame($line->id->value(), $regen->id->value());
        // Все остальные поля сохраняются
        self::assertSame($line->value, $regen->value);
        self::assertSame($line->email, $regen->email);
        self::assertSame($line->festivalId->value(), $regen->festivalId->value());
        self::assertSame($line->ticketTypeId?->value(), $regen->ticketTypeId?->value());
        self::assertTrue($line->price->equals($regen->price));
    }

    public function test_with_value_returns_new_object_with_changed_value(): void
    {
        $line = $this->makeRegularLine();
        $renamed = $line->withValue('Пётр Петров');

        self::assertNotSame($line, $renamed);
        self::assertSame('Пётр Петров', $renamed->value);
        // ID, email, цена не меняются
        self::assertSame($line->id->value(), $renamed->id->value());
        self::assertSame($line->email, $renamed->email);
        self::assertTrue($line->price->equals($renamed->price));
    }

    public function test_with_email_returns_new_object_with_changed_email(): void
    {
        $line = $this->makeRegularLine();
        $rewired = $line->withEmail('new@example.com');
        $cleared = $line->withEmail(null);

        self::assertSame('new@example.com', $rewired->email);
        self::assertNull($cleared->email);
        // Исходный не изменился (immutability)
        self::assertSame('ivan@example.com', $line->email);
    }

    public function test_with_number_assigns_live_ticket_number(): void
    {
        $line = $this->makeRegularLine();
        $numbered = $line->withNumber(123);

        self::assertSame(123, $numbered->number);
        self::assertNull($line->number);  // immutability
    }

    public function test_to_array_includes_all_new_fields(): void
    {
        $line = new OrderGuestLine(
            id: new Uuid(self::GUEST_ID),
            value: 'Иван',
            email: 'ivan@example.com',
            number: 7,
            festivalId: new Uuid(self::FESTIVAL_ID),
            ticketTypeId: new Uuid(self::TICKET_TYPE_ID_REGULAR),
            options: [
                new OrderGuestOption(new Uuid(self::SAPLING_OPTION_ID), 'Саженец', new Money(500)),
            ],
            promoCode: 'PROMO10',
            price: new MoneySnapshot(new Money(4200), new Money(500), new Money(200)),
            isLiveTicket: true,
        );

        $arr = $line->toArray();

        self::assertSame(self::GUEST_ID, $arr['id']);
        self::assertSame('Иван', $arr['value']);
        self::assertSame('ivan@example.com', $arr['email']);
        self::assertSame(7, $arr['number']);
        self::assertSame(self::FESTIVAL_ID, $arr['festival_id']);
        self::assertSame(self::TICKET_TYPE_ID_REGULAR, $arr['ticket_type_id']);
        self::assertCount(1, $arr['options']);
        self::assertSame(self::SAPLING_OPTION_ID, $arr['options'][0]['option_id']);
        self::assertSame('PROMO10', $arr['promo_code']);
        self::assertSame(4200, $arr['price_snapshot']['base_price']);
        self::assertSame(4500, $arr['price_snapshot']['total']);
        self::assertTrue($arr['is_live_ticket']);
    }

    public function test_to_array_for_list_order_serializes_null_ticket_type(): void
    {
        $line = new OrderGuestLine(
            id: new Uuid(self::GUEST_ID),
            value: 'Гость списка',
            email: null,
            number: null,
            festivalId: new Uuid(self::FESTIVAL_ID),
            ticketTypeId: null,
            options: [],
            promoCode: null,
            price: MoneySnapshot::zero(),
        );

        $arr = $line->toArray();

        self::assertNull($arr['ticket_type_id']);
        self::assertSame([], $arr['options']);
        self::assertNull($arr['promo_code']);
    }

    public function test_from_state_round_trips_to_array(): void
    {
        $original = new OrderGuestLine(
            id: new Uuid(self::GUEST_ID),
            value: 'Иван',
            email: 'ivan@example.com',
            number: 7,
            festivalId: new Uuid(self::FESTIVAL_ID),
            ticketTypeId: new Uuid(self::TICKET_TYPE_ID_REGULAR),
            options: [
                new OrderGuestOption(new Uuid(self::SAPLING_OPTION_ID), 'Саженец', new Money(500)),
            ],
            promoCode: 'PROMO10',
            price: new MoneySnapshot(new Money(4200), new Money(500), new Money(200)),
            isLiveTicket: true,
        );

        $restored = OrderGuestLine::fromState($original->toArray());

        self::assertSame($original->id->value(), $restored->id->value());
        self::assertSame($original->value, $restored->value);
        self::assertSame($original->email, $restored->email);
        self::assertSame($original->number, $restored->number);
        self::assertSame($original->ticketTypeId?->value(), $restored->ticketTypeId?->value());
        self::assertSame($original->promoCode, $restored->promoCode);
        self::assertSame($original->isLiveTicket, $restored->isLiveTicket);
        self::assertCount(1, $restored->options);
        self::assertTrue($original->options[0]->equals($restored->options[0]));
        self::assertTrue($original->price->equals($restored->price));
    }

    /**
     * @dataProvider missingRequiredFieldProvider
     */
    public function test_from_state_rejects_payload_without_required_field(string $missingKey, array $payload): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($missingKey);

        OrderGuestLine::fromState($payload);
    }

    public static function missingRequiredFieldProvider(): array
    {
        $priceSnapshot = ['base_price' => 4200, 'options_sum' => 0, 'discount' => 0];
        $basePayload = [
            'id' => self::GUEST_ID,
            'value' => 'Иван',
            'festival_id' => self::FESTIVAL_ID,
            'price_snapshot' => $priceSnapshot,
        ];

        return [
            'missing id' => ['id', array_diff_key($basePayload, ['id' => 0])],
            'missing value' => ['value', array_diff_key($basePayload, ['value' => 0])],
            'missing festival_id' => ['festival_id', array_diff_key($basePayload, ['festival_id' => 0])],
            'missing price_snapshot' => ['price_snapshot', array_diff_key($basePayload, ['price_snapshot' => 0])],
        ];
    }

    public function test_from_state_with_only_required_fields_uses_defaults(): void
    {
        // Минимальный валидный payload: только обязательные поля. Опциональные
        // (email/number/ticket_type_id/options/promo_code/is_live_ticket) → дефолты.
        $line = OrderGuestLine::fromState([
            'id' => self::GUEST_ID,
            'value' => 'Минимальный гость',
            'festival_id' => self::FESTIVAL_ID,
            'price_snapshot' => ['base_price' => 4200, 'options_sum' => 0, 'discount' => 0],
        ]);

        self::assertSame('Минимальный гость', $line->value);
        self::assertNull($line->email);
        self::assertNull($line->number);
        self::assertNull($line->ticketTypeId);
        self::assertSame([], $line->options);
        self::assertNull($line->promoCode);
        self::assertFalse($line->isLiveTicket);
        self::assertSame(4200, $line->total()->amount());
    }

    public function test_multiple_same_options_are_stored_as_repeated_elements_not_qty(): void
    {
        // Решение встречи 2026-05-30: кратность через повторение, НЕ через qty
        $sapling = fn (): OrderGuestOption => new OrderGuestOption(
            new Uuid(self::SAPLING_OPTION_ID),
            'Саженец',
            new Money(500),
        );

        $line = new OrderGuestLine(
            id: new Uuid(self::GUEST_ID),
            value: 'Гость',
            email: null,
            number: null,
            festivalId: new Uuid(self::FESTIVAL_ID),
            ticketTypeId: new Uuid(self::TICKET_TYPE_ID_REGULAR),
            options: [$sapling(), $sapling(), $sapling()],  // 3 саженца
            promoCode: null,
            price: new MoneySnapshot(new Money(4200), new Money(1500), Money::zero()),  // 3 * 500
        );

        self::assertCount(3, $line->options);
        self::assertSame(5700, $line->total()->amount());  // 4200 + 1500
    }
}
