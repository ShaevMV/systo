<?php

declare(strict_types=1);

namespace Tests\Unit;

use Baza\Shared\Domain\ValueObject\ShiftPermission;
use PHPUnit\Framework\TestCase;

/**
 * Тесты VO защищаемых действий (ось 2 матрицы прав, Baza, Ф2 PR-1).
 */
class ShiftPermissionTest extends TestCase
{
    public function test_all_contains_key_actions(): void
    {
        $all = ShiftPermission::all();

        self::assertContains(ShiftPermission::TICKET_ENTER, $all);
        self::assertContains(ShiftPermission::REPORT_VIEW, $all);
        self::assertContains(ShiftPermission::SYNC_MANAGE, $all);
        self::assertContains(ShiftPermission::RBAC_MANAGE, $all);
        self::assertContains(ShiftPermission::FINANCE_VIEW, $all);
    }

    public function test_is_valid(): void
    {
        self::assertTrue(ShiftPermission::isValid('sync.manage'));
        self::assertFalse(ShiftPermission::isValid('sync.delete_everything'));
        self::assertFalse(ShiftPermission::isValid(''));
    }

    public function test_label(): void
    {
        self::assertSame('Синхронизация (экспорт/импорт)', ShiftPermission::label(ShiftPermission::SYNC_MANAGE));
        self::assertSame('unknown.action', ShiftPermission::label('unknown.action'));
    }

    public function test_catalog_shape(): void
    {
        $catalog = ShiftPermission::catalog();

        self::assertSame(count(ShiftPermission::all()), count($catalog));
        self::assertSame(['value', 'label'], array_keys($catalog[0]));
    }

    public function test_codes_use_dot_namespacing(): void
    {
        // Коды действий — в формате group.action (для читаемого permission:<action>)
        foreach (ShiftPermission::all() as $code) {
            self::assertMatchesRegularExpression('/^[a-z]+\.[a-z_]+$/', $code, "Код '$code' должен быть group.action");
        }
    }
}
