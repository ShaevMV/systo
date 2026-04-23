<?php

declare(strict_types=1);

namespace Tickets\Ticket\CreateTickets\Application\ChangeTicket;

use Bus;
use DomainException;
use Shared\Domain\Bus\Command\CommandHandler;
use Tickets\History\Domain\ActorType;
use Tickets\History\Dto\SaveHistoryDto;
use Tickets\History\Repositories\HistoryRepositoryInterface;
use Tickets\Order\OrderTicket\Domain\OrderTicket;
use Tickets\Order\OrderTicket\Repositories\OrderTicketRepositoryInterface;

class ChangeTicketCommandHandler implements CommandHandler
{
    public function __construct(
        private OrderTicketRepositoryInterface $orderTicketRepository,
        private Bus                            $bus,
        private HistoryRepositoryInterface     $historyRepository,
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

        foreach ($orderTicket->pullHistoryEvents() as $historyEvent) {
            $this->historyRepository->save(new SaveHistoryDto(
                aggregateId: $command->getOrderId()->value(),
                event:       $historyEvent,
                actorId:     $command->getActorId(),
                actorType:   ActorType::USER,
            ));
        }

        $this->orderTicketRepository->updateGuests(
            $command->getOrderId(),
            $orderTicket->getTicket(),
        );

        $this->bus::chain($list)->dispatch();
    }
}
