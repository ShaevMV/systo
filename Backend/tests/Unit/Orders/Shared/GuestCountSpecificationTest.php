<?php

declare(strict_types=1);

namespace Tests\Unit\Orders\Shared;

use PHPUnit\Framework\TestCase;
use Tickets\Orders\Shared\Specification\GuestCountSpecification;

/**
 * Тесты спецификации GuestCountSpecification.
 *
 * Проверяет: минимальное/максимальное количество гостей,
 * групповые билеты с groupLimit, одиночные билеты без лимита.
 */
class GuestCountSpecificationTest extends TestCase
{
    private GuestCountSpecification $specification;

    protected function setUp(): void
    {
        $this->specification = new GuestCountSpecification();
    }

    /** @test */
    public function single_guest_satisfies_default_min_guests(): void
    {
        $result = $this->specification->isSatisfiedBy([
            'guestCount' => 1,
            'groupLimit' => null,
            'minGuests'  => 1,
        ]);

        $this->assertTrue($result);
        $this->assertEmpty($this->specification->getErrors());
    }

    /** @test */
    public function zero_guests_fails_validation(): void
    {
        $result = $this->specification->isSatisfiedBy([
            'guestCount' => 0,
            'groupLimit' => null,
            'minGuests'  => 1,
        ]);

        $this->assertFalse($result);
        $this->assertArrayHasKey('guests', $this->specification->getErrors());
    }

    /** @test */
    public function guest_count_within_group_limit_passes(): void
    {
        $result = $this->specification->isSatisfiedBy([
            'guestCount' => 5,
            'groupLimit' => 10,
            'minGuests'  => 1,
        ]);

        $this->assertTrue($result);
    }

    /** @test */
    public function guest_count_equal_to_group_limit_passes(): void
    {
        $result = $this->specification->isSatisfiedBy([
            'guestCount' => 10,
            'groupLimit' => 10,
            'minGuests'  => 1,
        ]);

        $this->assertTrue($result);
    }

    /** @test */
    public function guest_count_exceeds_group_limit_fails(): void
    {
        $result = $this->specification->isSatisfiedBy([
            'guestCount' => 11,
            'groupLimit' => 10,
            'minGuests'  => 1,
        ]);

        $this->assertFalse($result);
        $this->assertArrayHasKey('guests', $this->specification->getErrors());
        $this->assertStringContainsString('10', $this->specification->getErrors()['guests'][0]);
    }

    /** @test */
    public function null_group_limit_means_no_upper_bound(): void
    {
        $result = $this->specification->isSatisfiedBy([
            'guestCount' => 100,
            'groupLimit' => null,
            'minGuests'  => 1,
        ]);

        $this->assertTrue($result);
    }

    /** @test */
    public function custom_min_guests_is_respected(): void
    {
        $result = $this->specification->isSatisfiedBy([
            'guestCount' => 1,
            'groupLimit' => null,
            'minGuests'  => 2,
        ]);

        $this->assertFalse($result);
        $this->assertStringContainsString('2', $this->specification->getErrors()['guests'][0]);
    }

    /** @test */
    public function error_message_shows_correct_min_guests(): void
    {
        $this->specification->isSatisfiedBy([
            'guestCount' => 0,
            'groupLimit' => null,
            'minGuests'  => 3,
        ]);

        $errors = $this->specification->getErrors();
        $this->assertStringContainsString('3', $errors['guests'][0]);
    }

    /** @test */
    public function error_message_shows_correct_group_limit(): void
    {
        $this->specification->isSatisfiedBy([
            'guestCount' => 15,
            'groupLimit' => 10,
            'minGuests'  => 1,
        ]);

        $errors = $this->specification->getErrors();
        $this->assertStringContainsString('10', $errors['guests'][0]);
    }

    /** @test */
    public function errors_cleared_on_next_call(): void
    {
        $this->specification->isSatisfiedBy([
            'guestCount' => 0,
        ]);
        $this->assertNotEmpty($this->specification->getErrors());

        $this->specification->isSatisfiedBy([
            'guestCount' => 1,
            'groupLimit' => null,
        ]);
        $this->assertEmpty($this->specification->getErrors());
    }
}
