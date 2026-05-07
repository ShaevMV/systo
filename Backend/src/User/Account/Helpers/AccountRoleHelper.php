<?php

declare(strict_types=1);

namespace Tickets\User\Account\Helpers;

class AccountRoleHelper
{
    public const guest = 'guest'; // ГОСТь фестиваля
    public const admin = 'admin'; // Админ
    public const seller = 'seller'; // реализатор живых билетов
    public const pusher = 'pusher'; // реализатор френдли билетов
    public const manager = 'manager'; // реализатор френдли билетов
    public const curator = 'curator'; // куратор — создаёт заказы-списки на локации/сцены
    public const pusher_curator = 'pusher_curator'; // мульти-роль: pusher + curator

    public static function isValid(string $role): bool
    {
        return in_array($role, [
            self::pusher,
            self::seller,
            self::admin,
            self::guest,
            self::manager,
            self::curator,
            self::pusher_curator,
        ]);
    }
}
