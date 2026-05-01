<?php

declare(strict_types=1);

namespace Tickets\Orders\Shared\Contract;

use Shared\Domain\ValueObject\Status;
use Shared\Domain\ValueObject\Uuid;
use Tickets\Orders\Shared\Domain\BaseOrder;

/**
 * Политика доступа к операциям над заказом.
 *
 * Каждый тип заказа имеет свою политику, отражающую роли пользователей:
 * guest — обычный покупатель
 * seller — продавец живых билетов
 * pusher — продавец дружеских билетов
 * manager — менеджер анкет
 * admin — полный доступ
 *
 * Аналог middleware role:seller,admin,pusher — но перенесённый в доменный слой.
 */
interface OrderAccessPolicyInterface
{
    /**
     * Может ли пользователь с данной ролью создать заказ этого типа.
     */
    public function canCreate(string $role): bool;

    /**
     * Может ли пользователь просматривать список заказов этого типа.
     */
    public function canViewList(string $role): bool;

    /**
     * Может ли пользователь просматривать конкретный заказ.
     * Для обычных пользователей — только свои заказы (сравнение userId).
     */
    public function canViewItem(string $role, BaseOrder $order, ?Uuid $currentUserId = null): bool;

    /**
     * Может ли пользователь менять статус заказа на указанный.
     */
    public function canChangeStatus(string $role, Status $newStatus): bool;
}
