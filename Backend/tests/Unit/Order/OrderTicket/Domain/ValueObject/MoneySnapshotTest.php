<?php

declare(strict_types=1);

namespace Tests\Unit\Order\OrderTicket\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Shared\Domain\ValueObject\Money;
use Tickets\Order\OrderTicket\Domain\ValueObject\MoneySnapshot;

class MoneySnapshotTest extends TestCase
{
    public function test_zero_returns_snapshot_of_zero_money(): void
    {
        $snapshot = MoneySnapshot::zero();

        self::assertSame(0, $snapshot->basePrice->amount());
        self::assertSame(0, $snapshot->optionsSum->amount());
        self::assertSame(0, $snapshot->discount->amount());
        self::assertSame(0, $snapshot->total()->amount());
    }

    public function test_total_is_base_plus_options_minus_discount(): void
    {
        $snapshot = new MoneySnapshot(
            basePrice: new Money(4200),
            optionsSum: new Money(700),  // 500 саженец + 200 печатный
            discount: new Money(200),
        );

        // 4200 + 700 - 200 = 4700
        self::assertSame(4700, $snapshot->total()->amount());
    }

    public function test_total_without_options_or_discount(): void
    {
        $snapshot = new MoneySnapshot(
            basePrice: new Money(4200),
            optionsSum: Money::zero(),
            discount: Money::zero(),
        );

        self::assertSame(4200, $snapshot->total()->amount());
    }

    public function test_total_clamps_to_zero_when_discount_exceeds_sum(): void
    {
        // Защита Money::subtract — скидка больше базы+опций → итог 0, не отрицательно
        $snapshot = new MoneySnapshot(
            basePrice: new Money(100),
            optionsSum: new Money(50),
            discount: new Money(500),  // больше чем 150
        );

        self::assertSame(0, $snapshot->total()->amount());
    }

    public function test_to_array_serializes_all_fields_including_total(): void
    {
        $snapshot = new MoneySnapshot(
            basePrice: new Money(4200),
            optionsSum: new Money(700),
            discount: new Money(200),
        );

        $arr = $snapshot->toArray();

        self::assertSame([
            'base_price' => 4200,
            'options_sum' => 700,
            'discount' => 200,
            'total' => 4700,
        ], $arr);
    }

    public function test_from_state_round_trips_to_array(): void
    {
        $original = new MoneySnapshot(
            basePrice: new Money(4200),
            optionsSum: new Money(700),
            discount: new Money(200),
        );

        $restored = MoneySnapshot::fromState($original->toArray());

        self::assertTrue($original->equals($restored));
    }

    public function test_from_state_ignores_total_field_and_recomputes(): void
    {
        // Если в JSON пришёл `total`, он игнорируется — пересчитывается из компонентов.
        // Это защита от рассинхрона payload.
        $snapshot = MoneySnapshot::fromState([
            'base_price' => 4200,
            'options_sum' => 700,
            'discount' => 200,
            'total' => 99999,  // ← фальшивое значение, должно быть проигнорировано
        ]);

        self::assertSame(4700, $snapshot->total()->amount());
    }

    public function test_equals_checks_all_fields(): void
    {
        $a = new MoneySnapshot(new Money(100), new Money(50), new Money(10));
        $b = new MoneySnapshot(new Money(100), new Money(50), new Money(10));
        $c = new MoneySnapshot(new Money(100), new Money(50), new Money(20));

        self::assertTrue($a->equals($b));
        self::assertFalse($a->equals($c));
    }

    public function test_from_state_rejects_payload_without_base_price(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('base_price');

        MoneySnapshot::fromState(['options_sum' => 0, 'discount' => 0]);
    }

    public function test_from_state_rejects_payload_without_options_sum(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('options_sum');

        MoneySnapshot::fromState(['base_price' => 4200, 'discount' => 0]);
    }

    public function test_from_state_rejects_payload_without_discount(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('discount');

        MoneySnapshot::fromState(['base_price' => 4200, 'options_sum' => 0]);
    }

    public function test_from_state_rejects_empty_payload(): void
    {
        $this->expectException(InvalidArgumentException::class);

        MoneySnapshot::fromState([]);
    }
}
