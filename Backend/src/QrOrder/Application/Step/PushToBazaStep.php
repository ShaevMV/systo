<?php

declare(strict_types=1);

namespace Tickets\QrOrder\Application\Step;

use Tickets\BazaDelivery\Application\BazaDeliveryContext;
use Tickets\BazaDelivery\Application\BazaDeliveryDispatcher;
use Tickets\History\Domain\ActorType;
use Tickets\QrOrder\Application\Support\PipelineLog;
use Tickets\QrOrder\Dto\QrOrderDto;
use Tickets\Ticket\CreateTickets\Application\GetTicket\TicketResponse;

/**
 * Шаг 3: запись билетов заказа в Baza (el_tickets/spisok_tickets). На каждый билет ставит
 * трекаемую доставку через BazaDeliveryDispatcher (запись baza_deliveries + DeliverTicketToBazaJob,
 * свои ретраи, кап 10) — сбой Baza не валит выдачу/письмо, путь доставки виден в админке.
 *
 * Билеты без type_ticket_id в Baza не пишутся (нечего записать) — как в классическом флоу
 * (PushTicketsCommandHandler пропускает такие). Шаг сам никогда не бросает: только ставит доставки.
 */
final class PushToBazaStep implements PipelineStepInterface
{
    public function __construct(
        private readonly BazaDeliveryDispatcher $dispatcher,
    ) {
    }

    public function name(): string
    {
        return 'push_to_baza';
    }

    public function handle(QrOrderDto $order, array $carry): array
    {
        /** @var TicketResponse[] $responses */
        $responses = $carry['responses'] ?? [];
        $log = PipelineLog::logger();
        $queued = 0;

        foreach ($responses as $response) {
            // el_tickets-билету нужен type_ticket_id (как в классике). Списочный билет (isList)
            // пишется в spisok_tickets и в типе билета не нуждается.
            if (! $response->isList() && $response->getTypeTicketId() === null) {
                $log->warning('push_to_baza.skip_no_type', [
                    'order_id' => $order->getId()->value(),
                    'ticket_id' => $response->getId()->value(),
                ]);

                continue;
            }

            $this->dispatcher->dispatch(
                $response,
                new BazaDeliveryContext(source: 'qr_pipeline', actorType: ActorType::QR),
            );
            $queued++;
        }

        $log->info('push_to_baza.queued', [
            'order_id' => $order->getId()->value(),
            'count' => $queued,
        ]);

        return $carry;
    }
}
