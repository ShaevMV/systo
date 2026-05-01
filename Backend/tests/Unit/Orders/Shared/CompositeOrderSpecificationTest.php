<?php

declare(strict_types=1);

namespace Tests\Unit\Orders\Shared;

use PHPUnit\Framework\TestCase;
use Tickets\Orders\Shared\Contract\OrderSpecificationInterface;
use Tickets\Orders\Shared\Specification\CompositeOrderSpecification;

/**
 * Тесты CompositeOrderSpecification.
 *
 * Проверяет: пустой набор, прохождение всех, остановка на первой ошибке,
 * объединение ошибок.
 */
class CompositeOrderSpecificationTest extends TestCase
{
    /** @test */
    public function empty_specification_list_always_passes(): void
    {
        $composite = new CompositeOrderSpecification([]);
        $this->assertTrue($composite->isSatisfiedBy([]));
        $this->assertEmpty($composite->getErrors());
    }

    /** @test */
    public function all_passing_specifications_returns_true(): void
    {
        $composite = new CompositeOrderSpecification([
            $this->makeSpec(true),
            $this->makeSpec(true),
            $this->makeSpec(true),
        ]);

        $this->assertTrue($composite->isSatisfiedBy([]));
    }

    /** @test */
    public function first_failing_specification_stops_chain(): void
    {
        $failSpec     = $this->makeSpec(false, ['field' => ['error']]);
        $neverCalled  = $this->makeSpec(true); // не должен вызываться

        // Заменим neverCalled на Mock чтобы убедиться что он не вызывается
        $notCalledMock = $this->createMock(OrderSpecificationInterface::class);
        $notCalledMock->expects($this->never())->method('isSatisfiedBy');

        $composite = new CompositeOrderSpecification([
            $failSpec,
            $notCalledMock,
        ]);

        $result = $composite->isSatisfiedBy([]);

        $this->assertFalse($result);
    }

    /** @test */
    public function errors_from_failing_specification_are_collected(): void
    {
        $errors   = ['promo_code' => ['Промокод не найден']];
        $failSpec = $this->makeSpec(false, $errors);

        $composite = new CompositeOrderSpecification([$failSpec]);

        $composite->isSatisfiedBy([]);

        $this->assertSame($errors, $composite->getErrors());
    }

    /** @test */
    public function single_passing_spec_returns_no_errors(): void
    {
        $composite = new CompositeOrderSpecification([
            $this->makeSpec(true),
        ]);

        $composite->isSatisfiedBy([]);

        $this->assertEmpty($composite->getErrors());
    }

    // ----------------------------------------------------------------

    private function makeSpec(bool $passes, array $errors = []): OrderSpecificationInterface
    {
        return new class($passes, $errors) implements OrderSpecificationInterface {
            public function __construct(
                private readonly bool  $passes,
                private readonly array $errors,
            ) {}

            public function isSatisfiedBy(array $context): bool
            {
                return $this->passes;
            }

            public function getErrors(): array
            {
                return $this->errors;
            }
        };
    }
}
