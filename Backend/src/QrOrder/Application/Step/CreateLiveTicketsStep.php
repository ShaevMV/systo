<?php

declare(strict_types=1);

namespace Tickets\QrOrder\Application\Step;

use Carbon\Carbon;
use InvalidArgumentException;
use Shared\Domain\ValueObject\Uuid;
use Tickets\QrOrder\Application\Support\PipelineLog;
use Tickets\QrOrder\Application\Support\QrTicketId;
use Tickets\QrOrder\Dto\QrOrderDto;
use Tickets\QrOrder\Repositories\QrIssuanceRepositoryInterface;
use Tickets\Ticket\CreateTickets\Application\GetTicket\TicketResponse;
use Tickets\Ticket\CreateTickets\Dto\TicketDto;
use Tickets\Ticket\CreateTickets\Repositories\TicketsRepositoryInterface;

/**
 * Шаг создания билетов живого заказа: на каждого гостя создаёт el_ticket в tickets БЕЗ PDF
 * (festivalView=null → письмо PDF не прикрепит; ProcessCreatingQRCode НЕ ставим). Номер живого
 * билета берётся из guests[].number и кладётся в carry['liveLinks'] для шага связки с live_tickets.
 *
 * В отличие от обычного билета, в el_tickets живой билет не пишется — он связывается с уже
 * существующей строкой live_tickets по номеру (см. LinkLiveStep / setInBazaLive).
 */
final class CreateLiveTicketsStep implements PipelineStepInterface
{
    public function __construct(
        private readonly TicketsRepositoryInterface $ticketsRepository,
        private readonly QrIssuanceRepositoryInterface $issuanceRepository,
    ) {
    }

    public function name(): string
    {
        return 'create_live_tickets';
    }

    public function handle(QrOrderDto $order, array $carry): array
    {
        $festivalId = $order->getFestivalId();
        if ($festivalId === null) {
            throw new InvalidArgumentException('Выдача live невозможна: у заказа нет festival_id');
        }

        $payload = $order->getPayload();
        $guests = is_array($payload['guests'] ?? null) ? $payload['guests'] : [];
        $comment = $payload['order_data']['comment'] ?? null;

        /** @var TicketResponse[] $responses */
        $responses = [];
        $liveLinks = [];
        $firstTicketTypeId = null;
        $log = PipelineLog::logger();

        foreach ($guests as $index => $guest) {
            if (! is_array($guest)) {
                continue;
            }

            $ticketTypeId = $this->guestTicketTypeId($guest);
            $firstTicketTypeId ??= $ticketTypeId;
            $number = isset($guest['number']) ? (int) $guest['number'] : null;

            // Детерминированный id → идемпотентность: повтор выдачи не создаст дубль билета.
            $ticketId = QrTicketId::forGuest($order->getId(), $index);
            $email = (string) ($guest['email'] ?? $order->getEmail());

            $existingKilter = $this->issuanceRepository->getKilter($ticketId);
            if ($existingKilter === null) {
                $this->ticketsRepository->createTickets(new TicketDto(
                    $order->getId(),
                    (string) ($guest['name'] ?? ''),
                    $festivalId,
                    $ticketId,
                    email: $email,
                ));
            }

            $kilter = $existingKilter ?? $this->issuanceRepository->getKilter($ticketId) ?? 0;

            // Живой билет — без PDF: festivalView=null (письмо не прикрепит PDF), QR не генерим.
            $responses[] = new TicketResponse(
                name: (string) ($guest['name'] ?? ''),
                kilter: $kilter,
                uuid: $ticketId,
                status: 'paid',
                email: $email,
                phone: (string) ($order->getPhone() ?? ''),
                city: (string) ($order->getCity() ?? ''),
                comment: $comment,
                date_order: Carbon::now(),
                festivalView: null,
                emailView: null,
                festival_id: $festivalId,
                type_ticket_id: $ticketTypeId,
                type_ticket: isset($guest['type_ticket']['title']) ? (string) $guest['type_ticket']['title'] : null,
                order_id: $order->getId(),
            );

            if ($number !== null) {
                $liveLinks[] = ['ticket_id' => $ticketId->value(), 'number' => $number];
            } else {
                // Мягко: нет номера — нечего связывать с live_tickets (владелец гарантирует данные).
                $log->warning('create_live_tickets.no_number', [
                    'order_id' => $order->getId()->value(),
                    'guest_index' => $index,
                ]);
            }

            $log->info('create_live_tickets.guest_ok', [
                'order_id' => $order->getId()->value(),
                'guest_index' => $index,
                'number' => $number,
                'email' => PipelineLog::maskEmail($email),
            ]);
        }

        if ($responses === []) {
            $log->warning('create_live_tickets.no_guests', ['order_id' => $order->getId()->value()]);
        }

        $carry['responses'] = $responses;
        $carry['liveLinks'] = $liveLinks;
        $carry['firstTicketTypeId'] = $firstTicketTypeId;
        $carry['comment'] = $comment;

        return $carry;
    }

    /**
     * @param array<string, mixed> $guest
     */
    private function guestTicketTypeId(array $guest): ?Uuid
    {
        $id = $guest['type_ticket']['id'] ?? null;

        return is_string($id) && $id !== '' ? new Uuid($id) : null;
    }
}
