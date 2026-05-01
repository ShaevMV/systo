<?php

declare(strict_types=1);

namespace Tickets\Orders\Shared\Contract;

use Shared\Domain\ValueObject\Status;
use Tickets\Orders\Shared\Domain\BaseOrder;

/**
 * Pipeline переходов статусов для конкретного типа заказа.
 *
 * Каждый тип заказа имеет собственный Pipeline с индивидуальной матрицей переходов.
 * Логика «что происходит при переходе» остаётся в агрегате (Domain Events),
 * Pipeline отвечает только за валидацию допустимости перехода.
 */
interface OrderPipelineInterface
{
    /**
     * Проверяет, допустим ли переход из текущего статуса заказа в указанный.
     */
    public function canTransition(BaseOrder $order, Status $toStatus): bool;

    /**
     * Возвращает список допустимых следующих статусов в формате ['status_name' => 'Человеческое название'].
     */
    public function getAvailableTransitions(BaseOrder $order): array;
}
