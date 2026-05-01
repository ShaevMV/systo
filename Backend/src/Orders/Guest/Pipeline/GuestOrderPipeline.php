<?php

declare(strict_types=1);

namespace Tickets\Orders\Guest\Pipeline;

use Shared\Domain\ValueObject\Status;
use Tickets\Orders\Shared\Contract\OrderPipelineInterface;
use Tickets\Orders\Shared\Domain\BaseOrder;

/**
 * Матрица переходов статусов для гостевого заказа.
 *
 * Гостевой заказ — стандартная покупка через форму сайта.
 *
 * Граф переходов:
 *   NEW → PAID | CANCEL | DIFFICULTIES_AROSE
 *   PAID → DIFFICULTIES_AROSE
 *   DIFFICULTIES_AROSE → PAID | CANCEL
 *   CANCEL → (терминальный)
 */
final class GuestOrderPipeline implements OrderPipelineInterface
{
    private const TRANSITIONS = [
        Status::NEW => [
            Status::PAID,
            Status::CANCEL,
            Status::DIFFICULTIES_AROSE,
        ],
        Status::PAID => [
            Status::DIFFICULTIES_AROSE,
        ],
        Status::DIFFICULTIES_AROSE => [
            Status::PAID,
            Status::CANCEL,
        ],
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
