<?php

declare(strict_types=1);

namespace Tickets\Orders\Guest\Policy;

use Shared\Domain\ValueObject\Status;
use Shared\Domain\ValueObject\Uuid;
use Tickets\Orders\Shared\Contract\OrderAccessPolicyInterface;
use Tickets\Orders\Shared\Domain\BaseOrder;

/**
 * Политика доступа для гостевого заказа.
 *
 * Отражает текущие middleware-правила:
 * - create:        публичный (любой пользователь)
 * - getList:       admin, seller
 * - getItem:       auth + owner или admin/seller
 * - changeStatus:  admin, seller
 */
final class GuestOrderAccessPolicy implements OrderAccessPolicyInterface
{
    private const ROLES_CAN_VIEW_LIST     = ['admin', 'seller'];
    private const ROLES_CAN_CHANGE_STATUS = ['admin', 'seller'];

    public function canCreate(string $role): bool
    {
        return true;
    }

    public function canViewList(string $role): bool
    {
        return in_array($role, self::ROLES_CAN_VIEW_LIST, true);
    }

    public function canViewItem(string $role, BaseOrder $order, ?Uuid $currentUserId = null): bool
    {
        if (in_array($role, self::ROLES_CAN_VIEW_LIST, true)) {
            return true;
        }

        if ($currentUserId === null) {
            return false;
        }

        return $order->getUserId()->equals($currentUserId);
    }

    public function canChangeStatus(string $role, Status $newStatus): bool
    {
        return in_array($role, self::ROLES_CAN_CHANGE_STATUS, true);
    }
}
