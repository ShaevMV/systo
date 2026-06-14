<?php

declare(strict_types=1);

namespace Tickets\QrOrder\Application\Step;

use Carbon\Carbon;
use InvalidArgumentException;
use Shared\Domain\ValueObject\Uuid;
use Tickets\QrOrder\Application\Support\PipelineLog;
use Tickets\QrOrder\Dto\QrOrderDto;
use Tickets\QrOrder\Repositories\QrIssuanceRepositoryInterface;
use Tickets\Ticket\CreateTickets\Application\GetTicket\TicketResponse;
use Tickets\Ticket\CreateTickets\Domain\ProcessCreatingQRCode;
use Tickets\Ticket\CreateTickets\Dto\TicketDto;
use Tickets\Ticket\CreateTickets\Repositories\TicketsRepositoryInterface;

/**
 * Шаг 1: на каждого гостя создаёт билет в tickets (order_ticket_id == id qr-заказа),
 * читает kilter, собирает TicketResponse (шаблоны из ticket_type_festival по festival + type
 * билета гостя, минуя getTicket с JOIN на order_tickets) и ставит PDF/QR в очередь
 * (ProcessCreatingQRCode). Собранные TicketResponse кладутся в carry['responses'] для шага письма.
 *
 * Поведение «всё или ничего» (как в старом синхронном flow): любая ошибка пробрасывается →
 * оркестратор валит задачу → IssueOrderJob::failed() снимет issued_at, заказ можно выдать повторно.
 * (Per-guest изоляция вернётся позже — с per-guest шагами и отслеживанием частичного состояния.)
 */
final class CreateTicketsStep implements PipelineStepInterface
{
    public function __construct(
        private readonly TicketsRepositoryInterface $ticketsRepository,
        private readonly QrIssuanceRepositoryInterface $issuanceRepository,
    ) {
    }

    public function name(): string
    {
        return 'create_tickets';
    }

    public function handle(QrOrderDto $order, array $carry): array
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
        $log = PipelineLog::logger();

        foreach ($guests as $index => $guest) {
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

            $log->info('create_tickets.guest_ok', [
                'order_id' => $order->getId()->value(),
                'guest_index' => $index,
                'kilter' => $kilter,
                'email' => PipelineLog::maskEmail($email),
            ]);
        }

        if ($responses === []) {
            $log->warning('create_tickets.no_guests', ['order_id' => $order->getId()->value()]);
        }

        $carry['responses'] = $responses;
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
