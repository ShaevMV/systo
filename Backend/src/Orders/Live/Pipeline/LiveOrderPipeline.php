<?php

declare(strict_types=1);

namespace Tickets\Orders\Live\Pipeline;

use Shared\Domain\ValueObject\Status;
use Tickets\Orders\Shared\Contract\OrderPipelineInterface;
use Tickets\Orders\Shared\Domain\BaseOrder;

/**
 * Матрица переходов статусов для живого заказа.
 *
 * Живой заказ — покупка карточки live-билета с присвоением уникального номера.
 *
 * Граф переходов:
 *   NEW_FOR_LIVE → PAID_FOR_LIVE | CANCEL | DIFFICULTIES_AROSE | LIVE_TICKET_ISSUED
 *   PAID_FOR_LIVE → LIVE_TICKET_ISSUED | CANCEL_FOR_LIVE
 *   DIFFICULTIES_AROSE → PAID | CANCEL
 *   LIVE_TICKET_ISSUED → CANCEL_FOR_LIVE
 *   CANCEL → (терминальный)
 *   CANCEL_FOR_LIVE → (терминальный)
 */
final class LiveOrderPipeline implements OrderPipelineInterface
{
    private const TRANSITIONS = [
        Status::NEW_FOR_LIVE => [
            Status::PAID_FOR_LIVE,
            Status::CANCEL,
            Status::DIFFICULTIES_AROSE,
            Status::LIVE_TICKET_ISSUED,
        ],
        Status::PAID_FOR_LIVE => [
            Status::LIVE_TICKET_ISSUED,
            Status::CANCEL_FOR_LIVE,
        ],
        Status::DIFFICULTIES_AROSE => [
            Status::PAID,
            Status::CANCEL,
        ],
        Status::LIVE_TICKET_ISSUED => [
            Status::CANCEL_FOR_LIVE,
        ],
        Status::CANCEL         => [],
        Status::CANCEL_FOR_LIVE => [],
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
