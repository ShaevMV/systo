<?php

declare(strict_types=1);

namespace Tickets\QrOrder\Application\Issuance;

use Tickets\QrOrder\Application\Step\PipelineStepInterface;

/**
 * Стратегия выдачи по типу заказа (type_order). Определяет НАБОР и ПОРЯДОК шагов pipeline.
 *
 * Новый тип заказа = новый класс-стратегия + одна строка в реестре (TicketsProvider),
 * без правок оркестратора (Open-Closed Principle, Р. Мартин «Чистая архитектура»).
 */
interface IssuanceStrategyInterface
{
    /** Нормализованный ключ type_order, который обслуживает стратегия (см. TypeOrder). */
    public function typeOrder(): string;

    /**
     * Классы шагов в порядке выполнения. Резолвятся из контейнера (DI), реализуют PipelineStepInterface.
     *
     * @return array<int, class-string<PipelineStepInterface>>
     */
    public function steps(): array;
}
