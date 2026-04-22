<?php

declare(strict_types=1);

namespace Tickets\Ticket\CreateTickets\Application\ChangeTicket;

use Bus;
use DomainException;
use Shared\Domain\Bus\Command\CommandHandler;
use Tickets\Order\OrderTicket\Domain\OrderTicket;
use Tickets\Order\OrderTicket\Repositories\OrderTicketRepositoryInterface;

class ChangeTicketCommandHandler implements CommandHandler
{
    public function __construct(
        private OrderTicketRepositoryInterface $orderTicketRepository,
        private Bus                            $bus,
    ) {
    }

    public function __invoke(ChangeTicketCommand $command): void
    {
        $orderTicketDto = $this->orderTicketRepository->findOrder($command->getOrderId());

        if (is_null($orderTicketDto)) {
            throw new DomainException('Заказ не найден: ' . $command->getOrderId()->value());
        }

        $orderTicket = OrderTicket::toChangeTicket(
            $orderTicketDto,
            $command->getValueMap(),
            $command->getEmailMap(),
        );

        $list = $orderTicket->pullDomainEvents();

        $this->orderTicketRepository->updateGuests(
            $command->getOrderId(),
            $orderTicket->getTicket(),
        );

        $this->bus::chain($list)->dispatch();
    }
}
