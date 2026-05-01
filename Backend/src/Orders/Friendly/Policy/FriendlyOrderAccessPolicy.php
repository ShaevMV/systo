<?php

declare(strict_types=1);

namespace Tickets\Orders\Friendly\Policy;

use Shared\Domain\ValueObject\Status;
use Shared\Domain\ValueObject\Uuid;
use Tickets\Orders\Shared\Contract\OrderAccessPolicyInterface;
use Tickets\Orders\Shared\Domain\BaseOrder;

/**
 * Политика доступа для дружеского заказа.
 *
 * Отражает текущие middleware-правила:
 * - create:           role:pusher
 * - getListFriendly:  role:pusher,admin
 * - getItem:          pusher (владелец) или admin
 * - changeStatus:     role:seller,admin,pusher
 */
final class FriendlyOrderAccessPolicy implements OrderAccessPolicyInterface
{
    private const ROLES_CAN_CREATE        = ['pusher'];
    private const ROLES_CAN_VIEW_LIST     = ['pusher', 'admin'];
    private const ROLES_CAN_CHANGE_STATUS = ['seller', 'admin', 'pusher'];

    public function canCreate(string $role): bool
    {
        return in_array($role, self::ROLES_CAN_CREATE, true);
    }

    public function canViewList(string $role): bool
    {
        return in_array($role, self::ROLES_CAN_VIEW_LIST, true);
    }

    public function canViewItem(string $role, BaseOrder $order, ?Uuid $currentUserId = null): bool
    {
        if ($role === 'admin') {
            return true;
        }

        if ($role === 'pusher' && $currentUserId !== null) {
            return $order->getUserId()->equals($currentUserId);
        }

        return false;
    }

    public function canChangeStatus(string $role, Status $newStatus): bool
    {
        return in_array($role, self::ROLES_CAN_CHANGE_STATUS, true);
    }
}
