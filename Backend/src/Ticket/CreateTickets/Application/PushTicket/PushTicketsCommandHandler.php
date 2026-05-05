<?php

declare(strict_types=1);

namespace Tickets\Ticket\CreateTickets\Application\PushTicket;

use DomainException;
use Shared\Domain\Bus\Command\CommandHandler;
use Tickets\Ticket\CreateTickets\Repositories\TicketsRepositoryInterface;

class PushTicketsCommandHandler implements CommandHandler
{
    public function __construct(
        private TicketsRepositoryInterface $ticketsRepository
    )
    {
    }

    public function __invoke(PushTicketsCommand $command): void
    {
        $pushTicketsDto = $this->ticketsRepository->getTicket($command->getId(), true);

        // Заказы-списки → spisok_tickets, обычные → el_tickets.
        // Если нет ни куратора, ни type_ticket_id — нечего записывать в Baza.
        if ($pushTicketsDto->isList()) {
            $isOk = $this->ticketsRepository->setInBazaList($pushTicketsDto);
        } elseif ($pushTicketsDto->getTypeTicketId() !== null) {
            $isOk = $this->ticketsRepository->setInBaza($pushTicketsDto);
        } else {
            return;
        }

        if (!$isOk) {
            throw new DomainException('При записи произошла ошибка '. $command->getId()->value());
        };
    }
}
