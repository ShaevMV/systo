<?php

declare(strict_types=1);

namespace Tests\Unit\Order\OrderTicket\Application\Pricing\Dto;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Tickets\Order\OrderTicket\Application\Pricing\Dto\RawGuestOptionInput;

class RawGuestOptionInputTest extends TestCase
{
    private const OPTION_ID = 'a1111111-1111-1111-1111-111111111111';

    public function test_constructor_stores_fields(): void
    {
        $input = RawGuestOptionInput::fromState(['option_id' => self::OPTION_ID, 'qty' => 3]);

        self::assertSame(self::OPTION_ID, $input->optionId->value());
        self::assertSame(3, $input->qty);
    }

    public function test_qty_defaults_to_one_when_omitted(): void
    {
        $input = RawGuestOptionInput::fromState(['option_id' => self::OPTION_ID]);

        self::assertSame(1, $input->qty);
    }

    public function test_constructor_rejects_zero_qty(): void
    {
        // qty=0 — атакующий вектор: «опция вроде есть, но 0 раз учтена»
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('qty must be ≥ 1');

        RawGuestOptionInput::fromState(['option_id' => self::OPTION_ID, 'qty' => 0]);
    }

    public function test_constructor_rejects_negative_qty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('qty must be ≥ 1');

        RawGuestOptionInput::fromState(['option_id' => self::OPTION_ID, 'qty' => -2]);
    }

    public function test_constructor_rejects_qty_above_maximum(): void
    {
        // Защита от DoS: qty=999999 без верхнего предела развернулся бы в 999999 объектов.
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('qty must be ≤ ' . RawGuestOptionInput::MAX_QTY);

        RawGuestOptionInput::fromState([
            'option_id' => self::OPTION_ID,
            'qty' => RawGuestOptionInput::MAX_QTY + 1,
        ]);
    }

    public function test_constructor_accepts_qty_at_maximum_boundary(): void
    {
        // Граничный кейс: ровно MAX_QTY должно пройти, +1 — нет.
        $input = RawGuestOptionInput::fromState([
            'option_id' => self::OPTION_ID,
            'qty' => RawGuestOptionInput::MAX_QTY,
        ]);

        self::assertSame(RawGuestOptionInput::MAX_QTY, $input->qty);
    }

    public function test_from_state_accepts_numeric_string_qty(): void
    {
        // JSON-payload может прийти со строкой
        $input = RawGuestOptionInput::fromState(['option_id' => self::OPTION_ID, 'qty' => '5']);

        self::assertSame(5, $input->qty);
    }

    public function test_from_state_rejects_missing_option_id(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('option_id');

        RawGuestOptionInput::fromState(['qty' => 1]);
    }

    public function test_from_state_rejects_empty_option_id(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('option_id');

        RawGuestOptionInput::fromState(['option_id' => '', 'qty' => 1]);
    }

    /**
     * @dataProvider invalidQtyProvider
     */
    public function test_from_state_rejects_invalid_qty_types(mixed $invalidQty): void
    {
        // Аналог защиты в Money::fromInteger — отвергаем все типы, которые `(int) X` молча превратил бы в 0/1
        $this->expectException(InvalidArgumentException::class);

        RawGuestOptionInput::fromState([
            'option_id' => self::OPTION_ID,
            'qty' => $invalidQty,
        ]);
    }

    public static function invalidQtyProvider(): array
    {
        // null НЕ валидируется как invalid: `$data['qty'] ?? 1` даёт fallback 1 (валидный).
        // Это поведение «опустить ключ qty» = «qty = 1», и оно покрыто отдельным тестом
        // test_qty_defaults_to_one_when_omitted.
        return [
            'empty string' => [''],
            'non-numeric string' => ['abc'],
            'float string' => ['2.5'],
            'float value' => [2.5],
            'boolean true' => [true],
            'boolean false' => [false],
            'array' => [[1]],
        ];
    }

    public function test_explicit_null_qty_uses_default_one(): void
    {
        // `$data['qty'] ?? 1` — null триггерит null-coalesce, не считается «передан некорректный qty»
        $input = RawGuestOptionInput::fromState([
            'option_id' => self::OPTION_ID,
            'qty' => null,
        ]);

        self::assertSame(1, $input->qty);
    }
}
