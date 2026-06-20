<?php

declare(strict_types=1);

namespace Tickets\QrOrder\Application\Step;

use Tickets\BazaDelivery\Application\BazaDeliveryContext;
use Tickets\BazaDelivery\Application\BazaDeliveryDispatcher;
use Tickets\History\Domain\ActorType;
use Tickets\QrOrder\Application\Support\PipelineLog;
use Tickets\QrOrder\Application\Support\QrTicketId;
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
    ) {}

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

        // Карта детерминированный ticket_id → исходный гость (rich-данные для поиска без QR).
        // Тот же id, что в CreateTicketsStep (QrTicketId::forGuest) — связываем билет с гостем.
        $guestByTicketId = $this->mapGuestsByTicketId($order);
        $externalOrderNo = $order->getExternalOrderNo();

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

            $guest = $guestByTicketId[$response->getId()->value()] ?? [];

            $this->dispatcher->dispatch(
                $response,
                new BazaDeliveryContext(source: 'qr_pipeline', actorType: ActorType::QR),
                $this->buildSearch($guest, $externalOrderNo),
            );
            $queued++;
        }

        $log->info('push_to_baza.queued', [
            'order_id' => $order->getId()->value(),
            'count' => $queued,
        ]);

        return $carry;
    }

    /**
     * Сопоставить гостей контракта с билетами по детерминированному id (как в CreateTicketsStep).
     *
     * @return array<string, array<string, mixed>>
     */
    private function mapGuestsByTicketId(QrOrderDto $order): array
    {
        $guests = $order->getPayload()['guests'] ?? [];
        if (! is_array($guests)) {
            return [];
        }

        $map = [];
        foreach ($guests as $index => $guest) {
            if (! is_array($guest)) {
                continue;
            }
            $map[QrTicketId::forGuest($order->getId(), $index)->value()] = $guest;
        }

        return $map;
    }

    /**
     * Богатые поля гостя для поискового индекса Baza (ticket_search) — поиск без QR.
     *
     * @param  array<string, mixed>  $guest
     * @return array<string, mixed>
     */
    private function buildSearch(array $guest, ?string $externalOrderNo): array
    {
        $car = is_array($guest['car'] ?? null) ? $guest['car'] : [];
        $child = is_array($guest['child'] ?? null) ? $guest['child'] : [];
        $typeTicket = is_array($guest['type_ticket'] ?? null) ? $guest['type_ticket'] : [];

        $search = [
            'fio' => $guest['fio'] ?? ($guest['name'] ?? null),
            'phone' => $guest['phone'] ?? null,
            'telegram' => $guest['telegram'] ?? null,
            'email' => $guest['email'] ?? null,
            'city' => $guest['city'] ?? null,
            'car_number' => $car['number'] ?? null,
            'child_name' => $child['name'] ?? null,
            'parent_phone' => $child['parent_phone'] ?? null,
            'external_order_no' => $externalOrderNo,
            'type_ticket' => $typeTicket['title'] ?? null,
        ];

        // Отбрасываем пустые — пусть Baza применит fallback на узкий ticket для отсутствующих.
        return array_filter($search, static fn ($v) => $v !== null && $v !== '');
    }
}
