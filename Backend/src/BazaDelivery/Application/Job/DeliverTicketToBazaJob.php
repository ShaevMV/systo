<?php

declare(strict_types=1);

namespace Tickets\BazaDelivery\Application\Job;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RuntimeException;
use Shared\Domain\ValueObject\Uuid;
use Throwable;
use Tickets\Auto\Repositories\AutoRepositoryInterface;
use Tickets\BazaDelivery\Application\Client\BazaIngestClient;
use Tickets\BazaDelivery\Application\Support\BazaDeliveryLog;
use Tickets\BazaDelivery\Domain\BazaDeliveryLifecycleEvent;
use Tickets\BazaDelivery\Domain\ValueObject\BazaDeliveryStatus;
use Tickets\BazaDelivery\Dto\BazaDeliveryDto;
use Tickets\BazaDelivery\Repositories\BazaDeliveryRepositoryInterface;
use Tickets\History\Domain\ActorType;
use Tickets\History\Dto\SaveHistoryDto;
use Tickets\History\Repositories\HistoryRepositoryInterface;
use Tickets\Ticket\CreateTickets\Application\GetTicket\TicketResponse;
use Tickets\Ticket\CreateTickets\Repositories\TicketsRepositoryInterface;

/**
 * Асинхронная запись одного билета в Baza с трекингом каждой попытки. Зеркало SendEmailJob.
 *
 * queued → sending → delivered / failed. Несёт только id записи baza_deliveries; нужный билет
 * заново собирается из БД (getTicket по ticket_id) — поэтому повтор из админки = повторный dispatch.
 *
 * Кап = 10 попыток (§6.4): авто-ретрай и ручной resend суммарно ограничены 10 (attempts не
 * сбрасывается). После 10 неуспешных — терминальный failed, новые попытки записи в Baza не идут.
 */
final class DeliverTicketToBazaJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /** Жёсткий предел попыток доставки в Baza (авто-ретрай + ручной resend). */
    public const MAX_ATTEMPTS = 10;

    public int $tries = self::MAX_ATTEMPTS;

    /** @var int[] backoff между авто-ретраями (сек): 30с / 2м / 10м (далее — последнее значение). */
    public array $backoff = [30, 120, 600];

    public function __construct(private readonly string $deliveryId) {}

    public function handle(
        BazaDeliveryRepositoryInterface $repository,
        TicketsRepositoryInterface $tickets,
        HistoryRepositoryInterface $history,
        AutoRepositoryInterface $autos,
    ): void {
        $id = new Uuid($this->deliveryId);
        $delivery = $repository->findById($id);

        if ($delivery === null) {
            BazaDeliveryLog::logger()->error('baza.not_found', ['id' => $this->deliveryId]);

            return;
        }

        // Идемпотентность: уже доставлено — повторно в Baza не пишем.
        if ($delivery->getStatus() === BazaDeliveryStatus::DELIVERED) {
            return;
        }

        // Кап: после MAX_ATTEMPTS попыток — терминальный failed, новых попыток нет.
        if ($delivery->getAttempts() >= self::MAX_ATTEMPTS) {
            $repository->markFailed($id, 'Достигнут предел попыток доставки в Baza ('.self::MAX_ATTEMPTS.')');
            BazaDeliveryLog::logger()->error('baza.cap_reached', [
                'id' => $this->deliveryId,
                'attempts' => $delivery->getAttempts(),
            ]);

            return;
        }

        $repository->markSending($id);                 // attempts++
        $attemptNo = $delivery->getAttempts() + 1;
        $this->recordHistory($history, $delivery, BazaDeliveryStatus::SENDING, [
            'target' => $delivery->getTarget(),
            'attempt' => $attemptNo,
        ]);

        try {
            if (! $this->deliver($delivery, $repository, $tickets, $autos, app(BazaIngestClient::class))) {
                throw new RuntimeException('Запись билета в Baza вернула false');
            }

            $repository->markDelivered($id);
            $this->recordHistory($history, $delivery, BazaDeliveryStatus::DELIVERED, [
                'target' => $delivery->getTarget(),
                'attempt' => $attemptNo,
            ]);
            BazaDeliveryLog::logger()->info('baza.delivered', [
                'id' => $this->deliveryId,
                'target' => $delivery->getTarget(),
                'ticket_id' => $delivery->getTicketId()->value(),
                'attempt' => $attemptNo,
            ]);
        } catch (Throwable $e) {
            $repository->markFailed($id, $e->getMessage());
            $this->recordHistory($history, $delivery, BazaDeliveryStatus::FAILED, [
                'target' => $delivery->getTarget(),
                'attempt' => $attemptNo,
                'error' => $e->getMessage(),
            ]);
            BazaDeliveryLog::logger()->error('baza.failed', [
                'id' => $this->deliveryId,
                'target' => $delivery->getTarget(),
                'ticket_id' => $delivery->getTicketId()->value(),
                'attempt' => $attemptNo,
                'error' => $e->getMessage(),
            ]);

            // Авто-ретрай только пока не достигли капа; на последней попытке очередь не дёргаем.
            if ($attemptNo < self::MAX_ATTEMPTS) {
                throw $e;
            }

            BazaDeliveryLog::logger()->error('baza.cap_reached', [
                'id' => $this->deliveryId,
                'attempts' => $attemptNo,
            ]);
        }
    }

    /**
     * Маршрутизация записи в Baza по цели доставки.
     *
     * Каждая цель сперва пробует ingest-API Baza (если канал настроен), при сбое/неприменении —
     * текущая прямая запись в БД Baza (fallback). Канал выключен → сразу прямая запись (как раньше).
     */
    private function deliver(
        BazaDeliveryDto $delivery,
        BazaDeliveryRepositoryInterface $repository,
        TicketsRepositoryInterface $tickets,
        AutoRepositoryInterface $autos,
        BazaIngestClient $client,
    ): bool {
        // Богатые поля гостя для поискового индекса Baza (ticket_search) — если org их приложил.
        $search = $this->resolveSearch($delivery, $repository);

        switch ($delivery->getTarget()) {
            case 'el_tickets':
                $ticket = $this->resolveTicket($delivery, $repository, $tickets);

                return $this->ingestOrDirect($client, 'el_tickets', $ticket->toArrayForBaza(), $search, fn () => $tickets->setInBaza($ticket));
            case 'spisok_tickets':
                $ticket = $this->resolveTicket($delivery, $repository, $tickets);

                return $this->ingestOrDirect($client, 'spisok_tickets', $ticket->toArrayForSpisok(), $search, fn () => $tickets->setInBazaList($ticket));
            case 'live_tickets':
                $number = (int) $delivery->getNumber();
                $ticketId = $delivery->getTicketId();

                return $this->ingestOrDirect(
                    $client,
                    'live_tickets',
                    ['kilter' => $number, 'el_ticket_id' => $ticketId?->value()],
                    $search,
                    fn () => $tickets->setInBazaLive($number, $ticketId),
                );
            case 'auto':
                return $this->deliverAuto($delivery, $autos, $client, $search);
            default:
                throw new RuntimeException('Неизвестная цель доставки в Baza: '.$delivery->getTarget());
        }
    }

    /**
     * Ingest-API сначала, при сбое/неприменении — прямая запись (fallback).
     *
     * client->send: true → Baza применила (прямую запись пропускаем); false (явно не применила,
     * напр. нет live-номера) / null (канал выключен или ошибка транспорта) → прямая запись.
     *
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $search  богатые поля для ticket_search (пусто → fallback на ticket в Baza)
     * @param  callable(): bool  $direct
     */
    private function ingestOrDirect(BazaIngestClient $client, string $target, array $payload, array $search, callable $direct): bool
    {
        if ($client->send($target, $payload, $search) === true) {
            return true;
        }

        return (bool) $direct();
    }

    /**
     * Богатые поля гостя из search_blob (base64-json), приложенные org-доставкой. Пусто → [].
     *
     * @return array<string, mixed>
     */
    private function resolveSearch(BazaDeliveryDto $delivery, BazaDeliveryRepositoryInterface $repository): array
    {
        $blob = $repository->getSearchBlob($delivery->getId());
        if ($blob === null || $blob === '') {
            return [];
        }

        $decoded = json_decode((string) base64_decode($blob), true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Субъект доставки (TicketResponse) для el/spisok: из сохранённого blob (несёт верные данные,
     * в т.ч. для qr-билета), иначе fallback на getTicket (классический order_tickets-билет).
     */
    private function resolveTicket(
        BazaDeliveryDto $delivery,
        BazaDeliveryRepositoryInterface $repository,
        TicketsRepositoryInterface $tickets,
    ): TicketResponse {
        $blob = $repository->getSubjectBlob($delivery->getId());
        if ($blob !== null) {
            /** @var TicketResponse $ticket */
            $ticket = unserialize(base64_decode($blob));

            return $ticket;
        }

        return $tickets->getTicket($delivery->getTicketId(), true);
    }

    /**
     * Запись авто заказа-списка в Baza: пересобираем AutoDto по id, ingest-API → fallback setInBazaAuto.
     *
     * @param  array<string, mixed>  $search
     */
    private function deliverAuto(BazaDeliveryDto $delivery, AutoRepositoryInterface $autos, BazaIngestClient $client, array $search): bool
    {
        $auto = $autos->getById($delivery->getTicketId());
        if ($auto === null) {
            throw new RuntimeException('Авто не найдено: '.$delivery->getTicketId()->value());
        }

        $festivalId = $delivery->getFestivalId();
        $payload = [
            'order_id' => $auto->orderTicketId->value(),
            'auto' => $auto->number,
            'curator' => (string) ($auto->curator ?? ''),
            'project' => (string) ($auto->project ?? ''),
            'festival_id' => $festivalId,
        ];

        return $this->ingestOrDirect(
            $client,
            'auto',
            $payload,
            $search,
            fn () => $autos->setInBazaAuto($auto, $festivalId !== null ? new Uuid($festivalId) : null),
        );
    }

    /** Окончательный сбой задачи (tries исчерпаны) — фиксируем failed. */
    public function failed(Throwable $e): void
    {
        app(BazaDeliveryRepositoryInterface::class)->markFailed(new Uuid($this->deliveryId), $e->getMessage());
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function recordHistory(
        HistoryRepositoryInterface $history,
        BazaDeliveryDto $delivery,
        string $status,
        array $payload = [],
    ): void {
        $history->save(new SaveHistoryDto(
            $delivery->getId()->value(),
            new BazaDeliveryLifecycleEvent($status, $payload),
            null,
            str_starts_with($delivery->getSource(), 'qr') ? ActorType::QR : ActorType::SYSTEM,
        ));
    }
}
