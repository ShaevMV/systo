<?php

declare(strict_types=1);

namespace Tickets\QrOrder\Application\Step;

use Tickets\QrOrder\Dto\QrOrderDto;

/**
 * Один шаг pipeline выдачи билета. Шаги выполняются последовательно оркестратором
 * (IssueOrderJob); данные между шагами передаются через $carry (ассоц. массив). Каждый шаг —
 * одна зона ответственности (SRP), логируется оркестратором по имени name().
 */
interface PipelineStepInterface
{
    /** Короткое имя шага для логов (например, "create_tickets"). */
    public function name(): string;

    /**
     * @param  array<string, mixed>  $carry  данные предыдущих шагов
     * @return array<string, mixed> обновлённый carry
     */
    public function handle(QrOrderDto $order, array $carry): array;
}
