<?php

declare(strict_types=1);

namespace Tickets\BazaDelivery\Application;

use Shared\Domain\ValueObject\Uuid;
use Tickets\BazaDelivery\Application\Job\DeliverTicketToBazaJob;
use Tickets\BazaDelivery\Domain\BazaDeliveryLifecycleEvent;
use Tickets\BazaDelivery\Domain\ValueObject\BazaDeliveryStatus;
use Tickets\BazaDelivery\Dto\BazaDeliveryDto;
use Tickets\BazaDelivery\Repositories\BazaDeliveryRepositoryInterface;
use Tickets\History\Dto\SaveHistoryDto;
use Tickets\History\Repositories\HistoryRepositoryInterface;
use Tickets\Ticket\CreateTickets\Application\GetTicket\TicketResponse;

/**
 * Единая точка постановки доставки билета в Baza в очередь с трекингом. Зеркало MailDispatcher.
 *
 * Создаёт/возвращает в очередь запись baza_deliveries (queued, идемпотентно по (ticket_id, target)),
 * пишет историю baza_queued и ставит асинхронную задачу DeliverTicketToBazaJob. Сбой Baza больше не
 * рвёт выдачу билета/письма — доставка доедет ретраем (кап 10 попыток, см. §6.4).
 */
final class BazaDeliveryDispatcher
{
    public const TARGET_EL = 'el_tickets';
    public const TARGET_SPISOK = 'spisok_tickets';
    public const TARGET_LIVE = 'live_tickets';
    public const TARGET_AUTO = 'auto';

    public function __construct(
        private readonly BazaDeliveryRepositoryInterface $repository,
        private readonly HistoryRepositoryInterface $history,
    ) {
    }

    /**
     * Поставить доставку билета (обычного/списочного) в очередь. target выводится из билета:
     * списочный (isList) → spisok_tickets, иначе → el_tickets. Описательные поля — из самого билета.
     * Возвращает id записи baza_deliveries.
     */
    public function dispatch(TicketResponse $ticket, BazaDeliveryContext $ctx): Uuid
    {
        $target = $ticket->isList() ? self::TARGET_SPISOK : self::TARGET_EL;

        return $this->enqueue(
            $ticket->getId(),
            $target,
            new BazaDeliveryContext(
                orderId: $ticket->getOrderId()?->value(),
                festivalId: $ticket->getFestivalId()?->value(),
                name: $ticket->getName(),
                email: $ticket->getEmail(),
                number: $ticket->getKilter(),
                source: $ctx->source,
                actorType: $ctx->actorType,
            ),
        );
    }

    /**
     * Низкоуровневая постановка доставки в очередь по (билет, цель). Идемпотентно:
     *  - запись уже в delivered → не пере-доставляем (возвращаем существующий id);
     *  - иначе создаём queued (или возвращаем застрявшую в queued) + история + job.
     *
     * Используется dispatch() (el/spisok) и точечными вызовами live/auto (target задан явно).
     */
    public function enqueue(Uuid $ticketId, string $target, BazaDeliveryContext $ctx): Uuid
    {
        $existing = $this->repository->findByTicketTarget($ticketId, $target);

        if ($existing !== null) {
            if ($existing->getStatus() === BazaDeliveryStatus::DELIVERED) {
                return $existing->getId();
            }

            $id = $existing->getId();
            $this->repository->requeue($id);
        } else {
            $id = Uuid::random();
            $this->repository->create(BazaDeliveryDto::queued($id, $ticketId, $target, $ctx));
        }

        $this->history->save(new SaveHistoryDto(
            $id->value(),
            new BazaDeliveryLifecycleEvent(BazaDeliveryStatus::QUEUED, ['target' => $target, 'source' => $ctx->source]),
            null,
            $ctx->actorType,
        ));

        DeliverTicketToBazaJob::dispatch($id->value());

        return $id;
    }
}
