<?php

declare(strict_types=1);

namespace Tickets\Orders\Friendly\Pipeline;

use Shared\Domain\ValueObject\Status;
use Tickets\Orders\Shared\Contract\OrderPipelineInterface;
use Tickets\Orders\Shared\Domain\BaseOrder;

/**
 * Матрица переходов статусов для дружеского заказа.
 *
 * Дружеский заказ создаётся пушером уже в статусе PAID —
 * этап NEW отсутствует в жизненном цикле.
 *
 * Граф переходов:
 *   PAID → CANCEL
 *   CANCEL → (терминальный)
 */
final class FriendlyOrderPipeline implements OrderPipelineInterface
{
    private const TRANSITIONS = [
        Status::PAID   => [Status::CANCEL],
        Status::CANCEL => [],
    ];

    public function canTransition(BaseOrder $order, Status $toStatus): bool
    {
        $allowed = self::TRANSITIONS[(string)$order->getStatus()] ?? [];
        return in_array((string)$toStatus, $allowed, true);
    }

    public function getAvailableTransitions(BaseOrder $order): array
    {
        $statusNames = self::TRANSITIONS[(string)$order->getStatus()] ?? [];
        $result      = [];

        foreach ($statusNames as $statusName) {
            $result[$statusName] = (new Status($statusName))->getHumanStatus();
        }

        return $result;
    }
}
