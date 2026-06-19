<?php

declare(strict_types=1);

namespace Tickets\QrOrder\Application\Pipeline;

use Shared\Domain\ValueObject\Uuid;
use Tickets\BazaDelivery\Repositories\BazaDeliveryRepositoryInterface;
use Tickets\EmailDelivery\Repositories\EmailMessageRepositoryInterface;
use Tickets\History\Dto\DomainHistoryDto;
use Tickets\History\Repositories\HistoryRepositoryInterface;
use Tickets\QrOrder\Repositories\QrOrderRepositoryInterface;
use Tickets\Ticket\CreateTickets\Repositories\TicketsRepositoryInterface;

/**
 * Сборка «всего пути» qr-заказа для админки (Ф5): приём → создание билетов (PDF) →
 * письма (статусы доставки) → доставка в baza → история шагов. Только чтение; БД — в репозиториях.
 * Оркеструет репозитории, ничего не пишет.
 */
final class QrOrderPipelineReader
{
    public function __construct(
        private readonly QrOrderRepositoryInterface $orders,
        private readonly HistoryRepositoryInterface $history,
        private readonly EmailMessageRepositoryInterface $emails,
        private readonly TicketsRepositoryInterface $tickets,
        private readonly BazaDeliveryRepositoryInterface $baza,
    ) {}

    /** Ссылки на PDF билетов заказа (storage/tickets/{ticketId}.pdf). */
    public function ticketPdfUrls(Uuid $orderId): array
    {
        return array_map(
            static fn (Uuid $ticketId): string => asset('storage/tickets/'.$ticketId->value().'.pdf'),
            $this->tickets->getListIdByOrderId($orderId),
        );
    }

    /**
     * Весь путь заказа: заказ + таймлайн (created, status_changed, step_…, issued) + билеты с
     * PDF-ссылками + письма со статусами доставки. null → заказ не найден.
     */
    public function pipeline(Uuid $orderId): ?array
    {
        $order = $this->orders->findById($orderId);
        if ($order === null) {
            return null;
        }

        $tickets = array_map(
            static fn (Uuid $ticketId): array => [
                'ticket_id' => $ticketId->value(),
                'pdf_url' => asset('storage/tickets/'.$ticketId->value().'.pdf'),
            ],
            $this->tickets->getListIdByOrderId($orderId),
        );

        $history = array_map(
            static fn (DomainHistoryDto $h): array => [
                'event_name' => $h->eventName,
                'payload' => $h->payload,
                'actor_type' => $h->actorType,
                'occurred_at' => $h->occurredAt->toIso8601String(),
            ],
            $this->history->getByAggregateId($orderId->value()),
        );

        $emails = $this->emails->getByAggregate('qr_order', $orderId)
            ->map(static fn ($dto) => $dto->toArray())
            ->values()
            ->all();

        $baza = $this->baza->getByOrderId($orderId)
            ->map(static fn ($dto) => $dto->toArray())
            ->values()
            ->all();

        return [
            'order' => $order->toArray(),
            'tickets' => $tickets,
            'history' => $history,
            'emails' => $emails,
            'baza' => $baza,
        ];
    }
}
