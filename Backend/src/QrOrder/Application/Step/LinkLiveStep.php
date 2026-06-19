<?php

declare(strict_types=1);

namespace Tickets\QrOrder\Application\Step;

use Shared\Domain\ValueObject\Uuid;
use Tickets\BazaDelivery\Application\BazaDeliveryContext;
use Tickets\BazaDelivery\Application\BazaDeliveryDispatcher;
use Tickets\History\Domain\ActorType;
use Tickets\QrOrder\Application\Support\PipelineLog;
use Tickets\QrOrder\Dto\QrOrderDto;

/**
 * Шаг связки живых билетов с live_tickets: на каждую пару (ticket_id, number) из carry['liveLinks']
 * ставит трекаемую доставку через BazaDeliveryDispatcher (target=live_tickets, DeliverTicketToBazaJob,
 * свои ретраи, кап 10). Сам шаг не падает — только ставит доставки; путь виден в админке.
 */
final class LinkLiveStep implements PipelineStepInterface
{
    public function __construct(
        private readonly BazaDeliveryDispatcher $dispatcher,
    ) {
    }

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
            $this->dispatcher->dispatchLive(
                new Uuid($link['ticket_id']),
                (int) $link['number'],
                new BazaDeliveryContext(
                    orderId: $order->getId()->value(),
                    source: 'qr_pipeline',
                    actorType: ActorType::QR,
                ),
            );
            $queued++;
        }

        $log->info('link_live.queued', [
            'order_id' => $order->getId()->value(),
            'count' => $queued,
        ]);

        return $carry;
    }
}
