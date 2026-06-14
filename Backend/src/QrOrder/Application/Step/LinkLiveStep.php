<?php

declare(strict_types=1);

namespace Tickets\QrOrder\Application\Step;

use Tickets\QrOrder\Application\Job\LinkLiveTicketJob;
use Tickets\QrOrder\Application\Support\PipelineLog;
use Tickets\QrOrder\Dto\QrOrderDto;

/**
 * Шаг связки живых билетов с live_tickets: на каждую пару (ticket_id, number) из carry['liveLinks']
 * ставит изолированную задачу LinkLiveTicketJob (свои ретраи). Сам шаг не падает — только ставит задачи.
 */
final class LinkLiveStep implements PipelineStepInterface
{
    public function name(): string
    {
        return 'link_live';
    }

    public function handle(QrOrderDto $order, array $carry): array
    {
        /** @var array<int, array{ticket_id: string, number: int}> $links */
        $links = $carry['liveLinks'] ?? [];
        $log = PipelineLog::logger();
        $queued = 0;

        foreach ($links as $link) {
            LinkLiveTicketJob::dispatch($link['ticket_id'], $link['number']);
            $queued++;
        }

        $log->info('link_live.queued', [
            'order_id' => $order->getId()->value(),
            'count' => $queued,
        ]);

        return $carry;
    }
}
