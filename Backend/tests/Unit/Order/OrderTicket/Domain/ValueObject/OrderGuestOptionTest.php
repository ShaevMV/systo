<?php

declare(strict_types=1);

namespace Tests\Unit\Order\OrderTicket\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Shared\Domain\ValueObject\Money;
use Shared\Domain\ValueObject\Uuid;
use Tickets\Order\OrderTicket\Domain\ValueObject\OrderGuestOption;

class OrderGuestOptionTest extends TestCase
{
    private const SAPLING_ID = 'a1111111-1111-1111-1111-111111111111';

    public function test_constructor_stores_snapshot_fields(): void
    {
        $option = new OrderGuestOption(
            optionId: new Uuid(self::SAPLING_ID),
            nameSnapshot: 'Саженец',
            priceSnapshot: new Money(500),
        );

        self::assertSame(self::SAPLING_ID, $option->optionId->value());
        self::assertSame('Саженец', $option->nameSnapshot);
        self::assertSame(500, $option->priceSnapshot->amount());
    }

    public function test_to_array_serializes_for_json_payload(): void
    {
        $option = new OrderGuestOption(
            optionId: new Uuid(self::SAPLING_ID),
            nameSnapshot: 'Саженец',
            priceSnapshot: new Money(500),
        );

        self::assertSame([
            'option_id' => self::SAPLING_ID,
            'name' => 'Саженец',
            'price' => 500,
        ], $option->toArray());
    }

    public function test_from_state_round_trips_to_array(): void
    {
        $original = new OrderGuestOption(
            optionId: new Uuid(self::SAPLING_ID),
            nameSnapshot: 'Саженец',
            priceSnapshot: new Money(500),
        );

        $restored = OrderGuestOption::fromState($original->toArray());

        self::assertTrue($original->equals($restored));
    }

    public function test_equals_checks_all_fields(): void
    {
        $a = new OrderGuestOption(new Uuid(self::SAPLING_ID), 'Саженец', new Money(500));
        $b = new OrderGuestOption(new Uuid(self::SAPLING_ID), 'Саженец', new Money(500));

        // Разное имя — не равны (имя в снапшоте важно для аудита)
        $c = new OrderGuestOption(new Uuid(self::SAPLING_ID), 'Sapling', new Money(500));
        // Разная цена — не равны
        $d = new OrderGuestOption(new Uuid(self::SAPLING_ID), 'Саженец', new Money(600));

        self::assertTrue($a->equals($b));
        self::assertFalse($a->equals($c));
        self::assertFalse($a->equals($d));
    }

    public function test_from_state_rejects_payload_without_option_id(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('option_id');

        OrderGuestOption::fromState(['name' => 'Саженец', 'price' => 500]);
    }

    public function test_from_state_rejects_payload_without_name(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('name');

        OrderGuestOption::fromState(['option_id' => self::SAPLING_ID, 'price' => 500]);
    }

    public function test_from_state_rejects_payload_without_price(): void
    {
        // Критично: без strict-валидации payload без 'price' дал бы бесплатную опцию
        // (тихая порча данных / атакующий вектор).
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('price');

        OrderGuestOption::fromState(['option_id' => self::SAPLING_ID, 'name' => 'Саженец']);
    }

    /**
     * @dataProvider invalidPriceProvider
     */
    public function test_from_state_rejects_non_integer_price(mixed $invalidPrice): void
    {
        // Раньше `(int) $price` молча превращал null/""/"abc"/true/false → 0
        // → бесплатные опции через корявый payload (атакующий вектор!).
        // Теперь Money::fromInteger() кидает exception.
        $this->expectException(InvalidArgumentException::class);

        OrderGuestOption::fromState([
            'option_id' => self::SAPLING_ID,
            'name' => 'Саженец',
            'price' => $invalidPrice,
        ]);
    }

    public static function invalidPriceProvider(): array
    {
        return [
            'null → was silently 0 (FREE OPTION attack vector!)' => [null],
            'empty string → was silently 0' => [''],
            'non-numeric string → was silently 0' => ['abc'],
            'float string → was silently truncated' => ['12.5'],
            'float value' => [12.5],
            'boolean true → was silently 1' => [true],
            'boolean false → was silently 0' => [false],
        ];
    }

    public function test_from_state_accepts_numeric_string_price(): void
    {
        $option = OrderGuestOption::fromState([
            'option_id' => self::SAPLING_ID,
            'name' => 'Саженец',
            'price' => '500',
        ]);

        self::assertSame(500, $option->priceSnapshot->amount());
    }
}
