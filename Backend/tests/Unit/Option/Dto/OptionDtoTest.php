<?php

declare(strict_types=1);

namespace Tests\Unit\Option\Dto;

use PHPUnit\Framework\TestCase;
use Tickets\Option\Dto\OptionDto;

/**
 * Unit-тест для `OptionDto::fromState()`.
 *
 * Проверяет что DTO правильно собирается из ассоциативного массива
 * (как из БД, так и из request->data), а getter'ы возвращают
 * корректные типы.
 */
class OptionDtoTest extends TestCase
{
    /** @test */
    public function it_builds_dto_from_full_state(): void
    {
        $dto = OptionDto::fromState([
            'id' => '11111111-1111-1111-1111-111111111111',
            'name' => 'Саженец',
            'active' => true,
            'festival_id' => '22222222-2222-2222-2222-222222222222',
            'created_at' => '2026-06-01 10:00:00',
            'updated_at' => '2026-06-01 10:00:00',
        ]);

        $this->assertSame('11111111-1111-1111-1111-111111111111', $dto->getId()->value());
        $this->assertSame('Саженец', $dto->getName());
        $this->assertTrue($dto->getActive());
        $this->assertSame('22222222-2222-2222-2222-222222222222', $dto->getFestivalId()->value());
    }

    /** @test */
    public function it_generates_random_uuid_when_id_is_empty(): void
    {
        $dto = OptionDto::fromState([
            'name' => 'Без ID',
            'festival_id' => '22222222-2222-2222-2222-222222222222',
        ]);

        // Проверяем что сгенерирован валидный UUID v4
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $dto->getId()->value()
        );
    }

    /** @test */
    public function it_defaults_active_to_true_when_not_provided(): void
    {
        $dto = OptionDto::fromState([
            'name' => 'По умолчанию активна',
            'festival_id' => '22222222-2222-2222-2222-222222222222',
        ]);

        $this->assertTrue($dto->getActive(), 'Опция должна быть активна по умолчанию');
    }

    /** @test */
    public function it_respects_explicit_active_false(): void
    {
        $dto = OptionDto::fromState([
            'name' => 'Отключена',
            'active' => false,
            'festival_id' => '22222222-2222-2222-2222-222222222222',
        ]);

        $this->assertFalse($dto->getActive());
    }
}
