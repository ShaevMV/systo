<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\ValueObject;

/**
 * Источник комментария к заказу (кто оставил запись в треде).
 *
 *  - org_user — сотрудник админки org (admin/manager), user_id = его id;
 *  - baza     — персонал смены на КПП (через S2S от Baza), author_name = ФИО персонала;
 *  - qr       — витрина qr.spaceofjoy.ru (авто-комментарий из заказа), user_id = null;
 *  - system   — системная запись.
 */
final class CommentSource
{
    public const ORG_USER = 'org_user';

    public const BAZA = 'baza';

    public const QR = 'qr';

    public const SYSTEM = 'system';

    /**
     * @return string[]
     */
    public static function all(): array
    {
        return [self::ORG_USER, self::BAZA, self::QR, self::SYSTEM];
    }

    public static function isValid(string $source): bool
    {
        return in_array($source, self::all(), true);
    }
}
