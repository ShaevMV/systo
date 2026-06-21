<?php

declare(strict_types=1);

namespace Baza\Shared\Domain\ValueObject;

/**
 * Каталог защищаемых действий КПП — ось 2 матрицы прав «роль × действие» (Ф2).
 *
 * Пассивный enum-хелпер (без БД). Наличие строки (role, action) в
 * baza_role_permissions = право есть; отсутствие = запрет. administrator —
 * суперроль (короткозамкнута в коде, в таблицу не пишется).
 *
 * profile.* / login / logout — ВНЕ матрицы (доступны любому авторизованному).
 */
final class ShiftPermission
{
    public const TICKET_SCAN = 'ticket.scan';
    public const TICKET_SEARCH = 'ticket.search';
    public const TICKET_ENTER = 'ticket.enter';
    public const REPORT_VIEW = 'report.view';
    public const SHIFT_COMPOSE = 'shift.compose';
    public const SHIFT_CLOSE = 'shift.close';
    public const SHIFT_REMOVE = 'shift.remove';
    public const SYNC_MANAGE = 'sync.manage';
    public const FINANCE_VIEW = 'finance.view'; // зарезервировано под Ф7 (финансы смены)
    public const RBAC_MANAGE = 'rbac.manage';
    public const TICKET_PII = 'ticket.pii';     // полная карточка билета (телефон/email/коммент)
    public const STAFF_MANAGE = 'staff.manage'; // регистрация/управление персоналом (Шаг 5)

    public const LABELS = [
        self::TICKET_SCAN => 'Сканирование билета',
        self::TICKET_SEARCH => 'Ручной поиск билета',
        self::TICKET_ENTER => 'Впуск гостя',
        self::REPORT_VIEW => 'Просмотр отчёта/смен',
        self::SHIFT_COMPOSE => 'Формирование состава смены',
        self::SHIFT_CLOSE => 'Закрытие смены',
        self::SHIFT_REMOVE => 'Удаление смены',
        self::SYNC_MANAGE => 'Синхронизация (экспорт/импорт)',
        self::FINANCE_VIEW => 'Просмотр финансов смены',
        self::RBAC_MANAGE => 'Управление матрицей прав',
        self::TICKET_PII => 'Полная карточка билета (ПДн)',
        self::STAFF_MANAGE => 'Регистрация персонала',
    ];

    /**
     * @return string[] коды всех действий
     */
    public static function all(): array
    {
        return array_keys(self::LABELS);
    }

    public static function isValid(string $action): bool
    {
        return isset(self::LABELS[$action]);
    }

    public static function label(string $action): string
    {
        return self::LABELS[$action] ?? $action;
    }

    /**
     * Каталог для строк редактора матрицы прав.
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
}
