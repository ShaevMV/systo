<?php

declare(strict_types=1);

namespace Tickets\QrOrder\Application\Issuance;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Shared\Domain\ValueObject\Uuid;
use Throwable;
use Tickets\QrOrder\Application\Step\PipelineStepInterface;
use Tickets\QrOrder\Application\Support\PipelineLog;
use Tickets\QrOrder\Repositories\QrOrderRepositoryInterface;

/**
 * Оркестратор выдачи билетов по qr-заказу (асинхронно, вне HTTP-запроса qr).
 *
 * Резолвит стратегию по type_order и выполняет её шаги последовательно, логируя каждый
 * (start/ok/fail) в канал qr_pipeline. Данные между шагами идут через $carry. Шаги с
 * зависимостью по данным (билеты → письмо) потому и выполняются по порядку.
 *
 * tries=1: при сбое не перезапускаем автоматически (иначе дубль билетов/писем). issued_at
 * выставляется при dispatch (см. QrOrderApplication::changeStatus), повторного запуска нет.
 * Полноценная идемпотентность per-step добавляется в фазе 5.
 */
final class IssueOrderJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    private string $orderId;

    public function __construct(Uuid $orderId)
    {
        $this->orderId = $orderId->value();
    }

    public function handle(
        QrOrderRepositoryInterface $repository,
        IssuanceStrategyRegistry $registry,
        Container $container,
    ): void {
        $log = PipelineLog::logger();
        $order = $repository->findById(new Uuid($this->orderId));

        if ($order === null) {
            $log->error('pipeline.order_not_found', ['order_id' => $this->orderId]);

            return;
        }

        $strategy = $registry->resolve($order->getTypeOrder());
        $log->info('pipeline.start', [
            'order_id' => $this->orderId,
            'type_order' => $order->getTypeOrder(),
            'strategy' => $strategy->typeOrder(),
        ]);

        $carry = [];
        foreach ($strategy->steps() as $stepClass) {
            /** @var PipelineStepInterface $step */
            $step = $container->make($stepClass);

            $log->info('step.start', ['order_id' => $this->orderId, 'step' => $step->name()]);
            try {
                $carry = $step->handle($order, $carry);
                $log->info('step.ok', ['order_id' => $this->orderId, 'step' => $step->name()]);
            } catch (Throwable $e) {
                $log->error('step.fail', [
                    'order_id' => $this->orderId,
                    'step' => $step->name(),
                    'error' => $e->getMessage(),
                ]);

                throw $e;
            }
        }

        $log->info('pipeline.done', ['order_id' => $this->orderId]);
    }

    /**
     * Вызывается очередью при окончательном сбое задачи (tries исчерпаны).
     *
     * Снимаем отметку issued_at, чтобы заказ можно было выдать повторно (qr пришлёт «оплачен»
     * снова) — иначе заказ навсегда остался бы «выдан без билетов». Так восстанавливается
     * семантика старого синхронного flow (issued_at — только при фактическом успехе).
     */
    public function failed(Throwable $e): void
    {
        PipelineLog::logger()->critical('pipeline.failed', [
            'order_id' => $this->orderId,
            'error' => $e->getMessage(),
        ]);

        app(QrOrderRepositoryInterface::class)->clearIssued(new Uuid($this->orderId));
    }
}
