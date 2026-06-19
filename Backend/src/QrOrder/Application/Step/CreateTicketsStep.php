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
    ) {}

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
        $orderData = is_array($payload['order_data'] ?? null) ? $payload['order_data'] : [];
        $guests = is_array($payload['guests'] ?? null) ? $payload['guests'] : [];
        $comment = $orderData['comment'] ?? null;

        // Поля заказа-списка. Для regular/friendly отсутствуют → null → TicketResponse::isList()
        // вернёт false → билет уйдёт в el_tickets. Для list — заполнены → spisok_tickets.
        $curator = is_array($orderData['curator'] ?? null) ? $orderData['curator'] : [];
        $location = is_array($orderData['location'] ?? null) ? $orderData['location'] : [];
        $curatorId = empty($curator['id']) ? null : new Uuid((string) $curator['id']);
        $curatorEmail = isset($curator['email']) ? (string) $curator['email'] : null;
        $curatorName = isset($curator['name']) ? (string) $curator['name'] : null;
        $project = isset($orderData['project']) ? (string) $orderData['project'] : null;
        $locationId = empty($location['id']) ? null : new Uuid((string) $location['id']);

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

            // Детерминированный id → идемпотентность: повтор выдачи не создаст дубль билета.
            $ticketId = QrTicketId::forGuest($order->getId(), $index);
            $email = (string) ($guest['email'] ?? $order->getEmail());

            // 1. Создаём строку билета, только если её ещё нет (kilter — auto-increment в БД).
            $existingKilter = $this->issuanceRepository->getKilter($ticketId);
            $isNew = $existingKilter === null;
            if ($isNew) {
                $this->ticketsRepository->createTickets(new TicketDto(
                    $order->getId(),
                    (string) ($guest['name'] ?? $guest['fio'] ?? $guest['value'] ?? ''),
                    $festivalId,
                    $ticketId,
                    email: $email,
                ));
            }

            // 2. Читаем kilter + шаблоны под тип билета гостя.
            $kilter = $existingKilter ?? $this->issuanceRepository->getKilter($ticketId) ?? 0;
            $template = $ticketTypeId !== null
                ? $this->issuanceRepository->findTemplate($festivalId, $ticketTypeId)
                : null;

            // 3. Собираем TicketResponse вручную (без getTicket → без зависимости от order_tickets).
            $response = new TicketResponse(
                name: (string) ($guest['name'] ?? $guest['fio'] ?? $guest['value'] ?? ''),
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
                curator_id: $curatorId,
                curator_email: $curatorEmail,
                curator_name: $curatorName,
                project: $project,
                location_id: $locationId,
            );

            // 4. PDF/QR — только для новых билетов (повторная выдача не перегенерирует).
            if ($isNew) {
                ProcessCreatingQRCode::dispatch($response);
            }

            $responses[] = $response;

            $log->info($isNew ? 'create_tickets.guest_ok' : 'create_tickets.guest_reused', [
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
     * @param  array<string, mixed>  $guest
     */
    private function guestTicketTypeId(array $guest): ?Uuid
    {
        $id = $guest['type_ticket']['id'] ?? null;

        return is_string($id) && $id !== '' ? new Uuid($id) : null;
    }
}
