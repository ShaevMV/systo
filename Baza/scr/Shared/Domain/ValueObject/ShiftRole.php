<?php

declare(strict_types=1);

namespace Baza\Shared\Domain\ValueObject;

/**
 * Каталог 5 ролей в рамках смены КПП (Ф2).
 *
 * Пассивный enum-хелпер (без БД), по образцу Status. Коды латиницей —
 * для middleware/матрицы прав (baza_role_permissions); русские метки — для UI.
 * administrator — суперроль (в матрице прав короткозамкнута в коде).
 */
final class ShiftRole
{
    public const ADMINISTRATOR = 'administrator';
    public const SHIFT_CHIEF = 'shift_chief';
    public const TICKETER = 'ticketer';
    public const KPP_COMMANDANT = 'kpp_commandant';
    public const GUARD = 'guard';

    public const LABELS = [
        self::ADMINISTRATOR => 'Администратор',
        self::SHIFT_CHIEF => 'Начальник смены',
        self::TICKETER => 'Билетёр',
        self::KPP_COMMANDANT => 'Комендант КПП',
        self::GUARD => 'Охранник',
    ];

    /**
     * @return string[] коды всех ролей
     */
    public static function all(): array
    {
        return array_keys(self::LABELS);
    }

    public static function isValid(string $role): bool
    {
        return isset(self::LABELS[$role]);
    }

    public static function label(string $role): string
    {
        return self::LABELS[$role] ?? $role;
    }

    /**
     * Каталог для селектора в UI.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public static function catalog(): array
    {
        return array_map(
            static fn (string $code): array => ['value' => $code, 'label' => self::LABELS[$code]],
            self::all()
        );
    }

    /**
     * Мягкий маппинг текущего пользователя на роль (переходный период Ф2):
     * явная глобальная роль users.role, иначе is_admin → administrator / ticketer.
     * Сохраняет текущий вход: is_admin=true → administrator (полный доступ).
     */
    public static function fromUser(bool $isAdmin, ?string $role = null): string
    {
        if ($role !== null && self::isValid($role)) {
            return $role;
        }

        return $isAdmin ? self::ADMINISTRATOR : self::TICKETER;
    }
}
