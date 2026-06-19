<?php

declare(strict_types=1);

namespace Tickets\Ticket\CreateTickets\Application\PushTicketLive;

use DomainException;
use Shared\Domain\Bus\Command\CommandHandler;
use Tickets\BazaDelivery\Application\BazaDeliveryContext;
use Tickets\BazaDelivery\Application\BazaDeliveryDispatcher;
use Tickets\History\Domain\ActorType;
use Tickets\Ticket\CreateTickets\Repositories\TicketsRepositoryInterface;

/**
 * Связка живого билета с live_tickets при выдаче. Теперь — через BazaDeliveryDispatcher:
 * трекаемая доставка target=live_tickets + асинхронный DeliverTicketToBazaJob (ретраи, кап 10).
 * Сбой Baza больше не роняет выдачу; путь виден в админке.
 *
 * Редкий случай без билета (id=null, «снятие» связки) — пишем напрямую (трекать нечего).
 */
class PushTicketsLiveCommandHandler implements CommandHandler
{
    public function __construct(
        private TicketsRepositoryInterface $ticketsRepository,
        private BazaDeliveryDispatcher $dispatcher,
    ) {
    }

    public function __invoke(PushTicketsLiveCommand $command): void
    {
        $ticketId = $command->getId();

        if ($ticketId === null) {
            if (! $this->ticketsRepository->setInBazaLive($command->getNumber(), null)) {
                throw new DomainException('При записи произошла ошибка');
            }

            return;
        }

        $this->dispatcher->dispatchLive(
            $ticketId,
            $command->getNumber(),
            new BazaDeliveryContext(source: 'org_event', actorType: ActorType::SYSTEM),
        );
    }
}
