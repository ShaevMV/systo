<?php

declare(strict_types=1);

namespace Tickets\Ticket\CreateTickets\Application\PushTicket;

use Shared\Domain\Bus\Command\CommandHandler;
use Tickets\BazaDelivery\Application\BazaDeliveryContext;
use Tickets\BazaDelivery\Application\BazaDeliveryDispatcher;
use Tickets\History\Domain\ActorType;
use Tickets\Ticket\CreateTickets\Repositories\TicketsRepositoryInterface;

/**
 * Запись билета (обычный/список) в Baza при смене статуса. Теперь — через BazaDeliveryDispatcher:
 * трекаемая доставка baza_deliveries + асинхронный DeliverTicketToBazaJob (ретраи, кап 10).
 *
 * Важно: сбой Baza БОЛЬШЕ НЕ роняет смену статуса (раньше кидался DomainException) — билет/письмо
 * уже созданы, а доставка в Baza доедет ретраем. Путь доставки виден в админке «Доставка в baza».
 */
class PushTicketsCommandHandler implements CommandHandler
{
    public function __construct(
        private TicketsRepositoryInterface $ticketsRepository,
        private BazaDeliveryDispatcher $dispatcher,
    ) {
    }

    public function __invoke(PushTicketsCommand $command): void
    {
        $ticket = $this->ticketsRepository->getTicket($command->getId(), true);

        // Нечего записывать в Baza: ни куратора (список), ни type_ticket_id (обычный).
        if (! $ticket->isList() && $ticket->getTypeTicketId() === null) {
            return;
        }

        // Асинхронно + трекинг. target (el_tickets/spisok_tickets) выводится из билета внутри dispatch.
        $this->dispatcher->dispatch(
            $ticket,
            new BazaDeliveryContext(source: 'org_event', actorType: ActorType::SYSTEM),
        );
    }
}
