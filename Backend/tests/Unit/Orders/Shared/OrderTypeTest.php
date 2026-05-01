<?php

declare(strict_types=1);

namespace Tests\Unit\Orders\Shared;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Tickets\Orders\Shared\Domain\ValueObject\OrderType;

/**
 * Тесты Value Object OrderType.
 *
 * Проверяет: допустимые значения, kilter-префиксы, форматирование номера заказа,
 * и отклонение недопустимых значений.
 */
class OrderTypeTest extends TestCase
{
    /** @test */
    public function guest_order_type_has_correct_kilter_prefix(): void
    {
        $type = OrderType::fromString(OrderType::GUEST);
        $this->assertSame('G', $type->getKilterPrefix());
    }

    /** @test */
    public function friendly_order_type_has_correct_kilter_prefix(): void
    {
        $type = OrderType::fromString(OrderType::FRIENDLY);
        $this->assertSame('F', $type->getKilterPrefix());
    }

    /** @test */
    public function live_order_type_has_correct_kilter_prefix(): void
    {
        $type = OrderType::fromString(OrderType::LIVE);
        $this->assertSame('L', $type->getKilterPrefix());
    }

    /** @test */
    public function forest_card_order_type_has_correct_kilter_prefix(): void
    {
        $type = OrderType::fromString(OrderType::FOREST_CARD);
        $this->assertSame('LC', $type->getKilterPrefix());
    }

    /** @test */
    public function list_order_type_has_correct_kilter_prefix(): void
    {
        $type = OrderType::fromString(OrderType::LIST);
        $this->assertSame('S', $type->getKilterPrefix());
    }

    /** @test */
    public function parking_order_type_has_correct_kilter_prefix(): void
    {
        $type = OrderType::fromString(OrderType::PARKING);
        $this->assertSame('P', $type->getKilterPrefix());
    }

    /** @test */
    public function format_kilter_returns_prefix_dash_number(): void
    {
        $type = OrderType::fromString(OrderType::GUEST);
        $this->assertSame('G-1042', $type->formatKilter(1042));
    }

    /** @test */
    public function friendly_format_kilter_starts_from_one(): void
    {
        $type = OrderType::fromString(OrderType::FRIENDLY);
        $this->assertSame('F-1', $type->formatKilter(1));
    }

    /** @test */
    public function live_format_kilter_is_correct(): void
    {
        $type = OrderType::fromString(OrderType::LIVE);
        $this->assertSame('L-42', $type->formatKilter(42));
    }

    /** @test */
    public function invalid_order_type_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        OrderType::fromString('invalid_type');
    }

    /** @test */
    public function all_valid_types_are_recognized(): void
    {
        $validTypes = [
            OrderType::GUEST,
            OrderType::FRIENDLY,
            OrderType::LIVE,
            OrderType::FOREST_CARD,
            OrderType::LIST,
            OrderType::PARKING,
        ];

        foreach ($validTypes as $typeValue) {
            $type = OrderType::fromString($typeValue);
            $this->assertSame($typeValue, $type->value());
        }
    }
}
