<?php

declare(strict_types=1);

namespace Tests\Unit\OptionPrice\Dto;

use PHPUnit\Framework\TestCase;
use Tickets\OptionPrice\Dto\OptionPriceDto;

/**
 * Unit-тест для `OptionPriceDto::fromState()`.
 *
 * Особое внимание: цена хранится в INT (рубли целиком).
 * Проверяем что входящая строка/число корректно кастится в int.
 */
class OptionPriceDtoTest extends TestCase
{
    /** @test */
    public function it_builds_dto_with_integer_price(): void
    {
        $dto = OptionPriceDto::fromState([
            'id' => '11111111-1111-1111-1111-111111111111',
            'option_id' => '22222222-2222-2222-2222-222222222222',
            'price' => 500,
            'before_date' => '2026-09-01 00:00:00',
        ]);

        $this->assertSame(500, $dto->getPrice());
        $this->assertIsInt($dto->getPrice(), 'Цена должна быть int (рубли, без копеек)');
    }

    /** @test */
    public function it_casts_string_price_to_integer(): void
    {
        // Из БД может прийти строкой — должно скастить
        $dto = OptionPriceDto::fromState([
            'id' => '11111111-1111-1111-1111-111111111111',
            'option_id' => '22222222-2222-2222-2222-222222222222',
            'price' => '750',
            'before_date' => '2026-09-01',
        ]);

        $this->assertSame(750, $dto->getPrice());
    }

    /** @test */
    public function it_truncates_decimal_to_integer(): void
    {
        // Если кто-то по ошибке передаст float — обрежется до int
        $dto = OptionPriceDto::fromState([
            'option_id' => '22222222-2222-2222-2222-222222222222',
            'price' => 500.99,
            'before_date' => '2026-09-01',
        ]);

        $this->assertSame(500, $dto->getPrice());
    }

    /** @test */
    public function it_parses_before_date_as_carbon(): void
    {
        $dto = OptionPriceDto::fromState([
            'option_id' => '22222222-2222-2222-2222-222222222222',
            'price' => 500,
            'before_date' => '2026-09-01 00:00:00',
        ]);

        $this->assertSame('2026-09-01', $dto->getBeforeDate()->toDateString());
    }
}
