<?php

declare(strict_types=1);

namespace Tests\Unit;

use Baza\Shared\Domain\ValueObject\ShiftRole;
use PHPUnit\Framework\TestCase;

/**
 * Тесты VO ролей смены (Baza, Ф2 PR-1).
 */
class ShiftRoleTest extends TestCase
{
    public function test_all_returns_five_roles(): void
    {
        self::assertCount(5, ShiftRole::all());
        self::assertContains(ShiftRole::ADMINISTRATOR, ShiftRole::all());
        self::assertContains(ShiftRole::SHIFT_CHIEF, ShiftRole::all());
        self::assertContains(ShiftRole::TICKETER, ShiftRole::all());
        self::assertContains(ShiftRole::KPP_COMMANDANT, ShiftRole::all());
        self::assertContains(ShiftRole::GUARD, ShiftRole::all());
    }

    public function test_is_valid(): void
    {
        self::assertTrue(ShiftRole::isValid('shift_chief'));
        self::assertFalse(ShiftRole::isValid('superuser'));
        self::assertFalse(ShiftRole::isValid(''));
    }

    public function test_label(): void
    {
        self::assertSame('Начальник смены', ShiftRole::label(ShiftRole::SHIFT_CHIEF));
        // неизвестный код возвращается как есть (без исключения)
        self::assertSame('unknown', ShiftRole::label('unknown'));
    }

    public function test_catalog_shape(): void
    {
        $catalog = ShiftRole::catalog();

        self::assertCount(5, $catalog);
        self::assertSame(['value', 'label'], array_keys($catalog[0]));
        self::assertSame(ShiftRole::ADMINISTRATOR, $catalog[0]['value']);
        self::assertSame('Администратор', $catalog[0]['label']);
    }

    public function test_from_user_maps_is_admin(): void
    {
        // Сохранение текущего входа: is_admin=true → administrator, false → ticketer
        self::assertSame(ShiftRole::ADMINISTRATOR, ShiftRole::fromUser(true));
        self::assertSame(ShiftRole::TICKETER, ShiftRole::fromUser(false));
    }

    public function test_explicit_role_overrides_is_admin_mapping(): void
    {
        self::assertSame(ShiftRole::GUARD, ShiftRole::fromUser(false, ShiftRole::GUARD));
        self::assertSame(ShiftRole::SHIFT_CHIEF, ShiftRole::fromUser(true, ShiftRole::SHIFT_CHIEF));
    }

    public function test_invalid_explicit_role_falls_back_to_is_admin_mapping(): void
    {
        self::assertSame(ShiftRole::ADMINISTRATOR, ShiftRole::fromUser(true, 'bogus'));
        self::assertSame(ShiftRole::TICKETER, ShiftRole::fromUser(false, 'bogus'));
    }
}
