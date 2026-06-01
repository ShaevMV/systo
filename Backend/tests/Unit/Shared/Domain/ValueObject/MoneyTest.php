<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Shared\Domain\ValueObject\Money;

class MoneyTest extends TestCase
{
    public function test_zero_returns_money_with_zero_amount(): void
    {
        $money = Money::zero();

        self::assertSame(0, $money->amount());
        self::assertTrue($money->isZero());
        self::assertSame('RUB', $money->currency());
    }

    public function test_from_float_rounds_to_int_rubles_banker_half_even(): void
    {
        self::assertSame(4200, Money::fromFloat(4200.0)->amount());
        self::assertSame(4200, Money::fromFloat(4200.49)->amount());
        self::assertSame(4201, Money::fromFloat(4200.51)->amount());
        // banker's rounding: 0.5 округляется до ближайшего чётного
        self::assertSame(4200, Money::fromFloat(4199.5)->amount());  // ближе к 4200 (чётное)
        self::assertSame(4200, Money::fromFloat(4200.5)->amount());  // ближе к 4200 (чётное)
        self::assertSame(4202, Money::fromFloat(4201.5)->amount());  // ближе к 4202 (чётное)
    }

    public function test_add_sums_amounts(): void
    {
        $a = new Money(3800);
        $b = new Money(500);

        $sum = $a->add($b);

        self::assertSame(4300, $sum->amount());
        // immutability: исходные объекты не меняются
        self::assertSame(3800, $a->amount());
        self::assertSame(500, $b->amount());
    }

    public function test_subtract_returns_difference(): void
    {
        $a = new Money(4200);
        $b = new Money(200);

        self::assertSame(4000, $a->subtract($b)->amount());
    }

    public function test_subtract_clamps_to_zero_when_result_would_be_negative(): void
    {
        $a = new Money(100);
        $b = new Money(500);

        self::assertSame(0, $a->subtract($b)->amount());
    }

    public function test_multiply_returns_product(): void
    {
        $money = new Money(3800);

        self::assertSame(11400, $money->multiply(3)->amount());
        self::assertSame(0, $money->multiply(0)->amount());
    }

    public function test_constructor_rejects_negative_amount(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Money(-1);
    }

    public function test_multiply_rejects_negative_factor(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new Money(100))->multiply(-2);
    }

    public function test_add_rejects_different_currencies(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new Money(100, 'RUB'))->add(new Money(50, 'USD'));
    }

    public function test_equals_checks_amount_and_currency(): void
    {
        self::assertTrue((new Money(100))->equals(new Money(100)));
        self::assertFalse((new Money(100))->equals(new Money(200)));
        self::assertFalse((new Money(100, 'RUB'))->equals(new Money(100, 'USD')));
    }

    public function test_is_greater_than(): void
    {
        self::assertTrue((new Money(200))->isGreaterThan(new Money(100)));
        self::assertFalse((new Money(100))->isGreaterThan(new Money(200)));
        self::assertFalse((new Money(100))->isGreaterThan(new Money(100)));
    }

    public function test_as_float_returns_amount_as_float(): void
    {
        self::assertSame(4200.0, (new Money(4200))->asFloat());
    }

    public function test_from_float_rejects_nan(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('non-finite');

        Money::fromFloat(NAN);
    }

    public function test_from_float_rejects_positive_infinity(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('non-finite');

        Money::fromFloat(INF);
    }

    public function test_from_float_rejects_negative_infinity(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('non-finite');

        Money::fromFloat(-INF);
    }

    public function test_from_float_rejects_out_of_int_range(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('out of int range');

        // PHP_INT_MAX = 9.22e18 на 64-bit. 1e20 заведомо больше.
        Money::fromFloat(1.0e20);
    }

    public function test_from_float_rejects_negative_value_via_constructor(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('cannot be negative');

        Money::fromFloat(-100.0);
    }

    public function test_add_rejects_int_overflow(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('overflow in add');

        // PHP_INT_MAX + 1 → без проверки PHP молча превратит результат в float.
        (new Money(PHP_INT_MAX))->add(new Money(1));
    }

    public function test_add_at_exact_int_max_is_allowed(): void
    {
        // граничный кейс: сумма === PHP_INT_MAX — должна проходить без ошибок
        $sum = (new Money(PHP_INT_MAX - 100))->add(new Money(100));

        self::assertSame(PHP_INT_MAX, $sum->amount());
    }

    public function test_multiply_rejects_int_overflow(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('overflow in multiply');

        // (PHP_INT_MAX / 2 + 1) * 2 > PHP_INT_MAX
        (new Money(intdiv(PHP_INT_MAX, 2) + 1))->multiply(2);
    }

    public function test_multiply_by_zero_returns_zero_without_overflow_check(): void
    {
        // factor === 0 — особый кейс: intdiv(X, 0) кинул бы DivisionByZeroError,
        // поэтому защита overflow пропускает 0. Результат всегда 0.
        $result = (new Money(PHP_INT_MAX))->multiply(0);

        self::assertTrue($result->isZero());
    }
}
