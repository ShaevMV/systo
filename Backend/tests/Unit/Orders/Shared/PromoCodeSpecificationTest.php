<?php

declare(strict_types=1);

namespace Tests\Unit\Orders\Shared;

use PHPUnit\Framework\TestCase;
use Tickets\Orders\Shared\Specification\PromoCodeSpecification;
use Tickets\PromoCode\Dto\LimitPromoCodeDto;
use Tickets\PromoCode\Response\PromoCodeDto;

/**
 * Тесты спецификации PromoCodeSpecification.
 *
 * Проверяет: отсутствие промокода (пропуск), невалидный промокод,
 * исчерпанный лимит, корректный промокод без лимита.
 */
class PromoCodeSpecificationTest extends TestCase
{
    private PromoCodeSpecification $specification;

    protected function setUp(): void
    {
        $this->specification = new PromoCodeSpecification();
    }

    /** @test */
    public function no_promo_code_name_passes_validation(): void
    {
        $result = $this->specification->isSatisfiedBy([]);

        $this->assertTrue($result);
        $this->assertEmpty($this->specification->getErrors());
    }

    /** @test */
    public function null_promo_code_name_passes_validation(): void
    {
        $result = $this->specification->isSatisfiedBy([
            'promoCodeName' => null,
        ]);

        $this->assertTrue($result);
    }

    /** @test */
    public function missing_promo_code_dto_fails_validation(): void
    {
        $result = $this->specification->isSatisfiedBy([
            'promoCodeName' => 'SUMMER2026',
            'promoCode'     => null,
        ]);

        $this->assertFalse($result);
        $this->assertArrayHasKey('promo_code', $this->specification->getErrors());
    }

    /** @test */
    public function inactive_promo_code_fails_validation(): void
    {
        $inactiveDto = new PromoCodeDto(
            limit:     new LimitPromoCodeDto(0, null),
            isSuccess: false,
        );

        $result = $this->specification->isSatisfiedBy([
            'promoCodeName' => 'SUMMER2026',
            'promoCode'     => $inactiveDto,
        ]);

        $this->assertFalse($result);
        $this->assertArrayHasKey('promo_code', $this->specification->getErrors());
    }

    /** @test */
    public function exhausted_limit_promo_code_fails_validation(): void
    {
        // count=5, limit=5 → isCorrect() = false (не больше, а равно)
        $limitDto = new LimitPromoCodeDto(count: 5, limit: 5);
        $dto = new PromoCodeDto(
            limit:     $limitDto,
            isSuccess: true,
        );

        $result = $this->specification->isSatisfiedBy([
            'promoCodeName' => 'LIMITED',
            'promoCode'     => $dto,
        ]);

        $this->assertFalse($result);
        $errors = $this->specification->getErrors();
        $this->assertArrayHasKey('promo_code', $errors);
        $this->assertStringContainsString('Лимит', $errors['promo_code'][0]);
    }

    /** @test */
    public function valid_promo_code_without_limit_passes(): void
    {
        $dto = new PromoCodeDto(
            limit:     new LimitPromoCodeDto(count: 100, limit: null),
            isSuccess: true,
        );

        $result = $this->specification->isSatisfiedBy([
            'promoCodeName' => 'UNLIMITED',
            'promoCode'     => $dto,
        ]);

        $this->assertTrue($result);
        $this->assertEmpty($this->specification->getErrors());
    }

    /** @test */
    public function valid_promo_code_within_limit_passes(): void
    {
        $dto = new PromoCodeDto(
            limit:     new LimitPromoCodeDto(count: 3, limit: 10),
            isSuccess: true,
        );

        $result = $this->specification->isSatisfiedBy([
            'promoCodeName' => 'PROMO10',
            'promoCode'     => $dto,
        ]);

        $this->assertTrue($result);
    }

    /** @test */
    public function errors_cleared_on_repeated_calls(): void
    {
        $this->specification->isSatisfiedBy([
            'promoCodeName' => 'BAD',
            'promoCode'     => null,
        ]);
        $this->assertNotEmpty($this->specification->getErrors());

        $this->specification->isSatisfiedBy([]);
        $this->assertEmpty($this->specification->getErrors());
    }
}
