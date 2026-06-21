<?php

declare(strict_types=1);

namespace Baza\Tickets\Services;

/**
 * Фильтр ПДн карточки билета по праву ticket.pii (Шаг 3, реш. владельца 2026-06-21).
 *
 * Полную карточку (телефон/email/коммент/телега/детские/госномер) видят только роли
 * с правом ticket.pii (administrator/начальник смены/комендант КПП — настраивается в матрице).
 * Билетёру/охране — без ПДн. Фильтрация на БЭКЕНДЕ (фронт обходят через DevTools).
 * Чистый сервис без БД — легко тестируется.
 */
final class TicketPiiFilter
{
    /** Ключи карточки, считающиеся ПДн (скрываются без права ticket.pii). */
    private const PII_KEYS = ['phone', 'email', 'comment', 'telegram', 'parent_phone', 'child_name', 'car_number'];

    /**
     * @param  array<string, mixed>  $card
     * @return array<string, mixed>
     */
    public static function apply(array $card, bool $canViewPii): array
    {
        if ($canViewPii) {
            return $card;
        }
        foreach (self::PII_KEYS as $key) {
            unset($card[$key]);
        }

        return $card;
    }
}
