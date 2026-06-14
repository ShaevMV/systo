<?php

declare(strict_types=1);

namespace Tickets\QrOrder\Application;

use App\Mail\OrderToPaid;
use Carbon\Carbon;
use InvalidArgumentException;
use Illuminate\Support\Facades\Mail;
use Psr\Log\LoggerInterface;
use Shared\Domain\ValueObject\Uuid;
use Tickets\QrOrder\Dto\QrOrderDto;
use Tickets\QrOrder\Repositories\QrIssuanceRepositoryInterface;
use Tickets\Ticket\CreateTickets\Application\GetTicket\TicketResponse;
use Tickets\Ticket\CreateTickets\Domain\ProcessCreatingQRCode;
use Tickets\Ticket\CreateTickets\Dto\TicketDto;
use Tickets\Ticket\CreateTickets\Repositories\TicketsRepositoryInterface;

/**
 * Автономная выдача билетов по qr-заказу (API №2b, решение B — без order_tickets).
 *
 * На каждого гостя: вставляем билет в tickets (order_ticket_id == id qr-заказа), читаем kilter,
 * собираем TicketResponse ВРУЧНУЮ (шаблоны PDF/email — из ticket_type_festival по
 * festival_id + type_ticket гостя, минуя getTicket с JOIN на order_tickets), генерируем PDF/QR
 * (ProcessCreatingQRCode) и шлём письмо с билетами (OrderToPaid). См. CONTRACT_RFC §6/§7.
 */
final class QrOrderIssuer
{
    public function __construct(
        private readonly TicketsRepositoryInterface $ticketsRepository,
        private readonly QrIssuanceRepositoryInterface $issuanceRepository,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function issue(QrOrderDto $order): void
    {
        $festivalId = $order->getFestivalId();
        if ($festivalId === null) {
            throw new InvalidArgumentException('Выдача невозможна: у заказа нет festival_id');
        }

        $payload = $order->getPayload();
        $guests = is_array($payload['guests'] ?? null) ? $payload['guests'] : [];
        $comment = $payload['order_data']['comment'] ?? null;

        /** @var TicketResponse[] $responses */
        $responses = [];
        $firstTicketTypeId = null;

        foreach ($guests as $guest) {
            if (! is_array($guest)) {
                continue;
            }
            $ticketTypeId = $this->guestTicketTypeId($guest);
            $firstTicketTypeId ??= $ticketTypeId;

            $ticketId = Uuid::random();
            $email = (string) ($guest['email'] ?? $order->getEmail());

            // 1. Создаём строку билета (kilter — auto-increment в БД).
            $this->ticketsRepository->createTickets(new TicketDto(
                $order->getId(),
                (string) ($guest['name'] ?? ''),
                $festivalId,
                $ticketId,
                email: $email,
            ));

            // 2. Читаем kilter + шаблоны под тип билета гостя.
            $kilter = $this->issuanceRepository->getKilter($ticketId) ?? 0;
            $template = $ticketTypeId !== null
                ? $this->issuanceRepository->findTemplate($festivalId, $ticketTypeId)
                : null;

            // 3. Собираем TicketResponse вручную (без getTicket → без зависимости от order_tickets).
            $response = new TicketResponse(
                name: (string) ($guest['name'] ?? ''),
                kilter: $kilter,
                uuid: $ticketId,
                status: 'paid',
                email: $email,
                phone: (string) ($order->getPhone() ?? ''),
                city: (string) ($order->getCity() ?? ''),
                comment: $comment,
                date_order: Carbon::now(),
                festivalView: $template['pdf'] ?? 'pdf',
                emailView: $template['email'] ?? null,
                festival_id: $festivalId,
                type_ticket_id: $ticketTypeId,
                type_ticket: isset($guest['type_ticket']['title']) ? (string) $guest['type_ticket']['title'] : null,
                order_id: $order->getId(),
            );

            // 4. Сохраняем PDF/QR (для скачивания) — очередь.
            ProcessCreatingQRCode::dispatch($response);

            $responses[] = $response;
        }

        if ($responses === []) {
            $this->logger->warning('[qr-issue] Нет гостей для выдачи', ['order_id' => $order->getId()->value()]);

            return;
        }

        // 5. Письмо получателю с PDF-билетами (PDF рендерится в самом письме).
        Mail::to($order->getEmail())->send(new OrderToPaid(
            $responses,
            $firstTicketTypeId ?? Uuid::random(),
            $comment,
            null,
        ));

        $this->logger->info('[qr-issue] Билеты выданы', [
            'order_id' => $order->getId()->value(),
            'tickets' => count($responses),
        ]);
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
