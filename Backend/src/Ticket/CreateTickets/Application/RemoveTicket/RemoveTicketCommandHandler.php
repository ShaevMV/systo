<?php

declare(strict_types=1);

namespace Tickets\Ticket\CreateTickets\Application\RemoveTicket;

use Bus;
use DomainException;
use Illuminate\Support\Facades\Log;
use Shared\Domain\Bus\Command\CommandHandler;
use Tickets\History\Domain\ActorType;
use Tickets\History\Dto\SaveHistoryDto;
use Tickets\History\Repositories\HistoryRepositoryInterface;
use Tickets\Order\OrderTicket\Domain\OrderTicket;
use Tickets\Order\OrderTicket\Repositories\OrderTicketRepositoryInterface;

class RemoveTicketCommandHandler implements CommandHandler
{
    public function __construct(
        private OrderTicketRepositoryInterface $orderTicketRepository,
        private Bus                            $bus,
        private HistoryRepositoryInterface     $historyRepository,
    ) {
    }

    public function __invoke(RemoveTicketCommand $command): void
    {
        $orderTicketDto = $this->orderTicketRepository->findOrder($command->getOrderId());

        if (is_null($orderTicketDto)) {
            throw new DomainException('Заказ не найден: ' . $command->getOrderId()->value());
        }

        $orderTicket = OrderTicket::toRemoveTicket(
            $orderTicketDto,
            $command->getOrderTicketId(),
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
        $ar = [];
        foreach ($orderTicket->getTicket() as $item) {
            $ar[]=$item->toArray();
        }
        Log::debug('Удаляем билет' . $command->getOrderTicketId()->value(),[
            $ar
        ]);
        $this->orderTicketRepository->updateGuests(
            $command->getOrderId(),
            $orderTicket->getTicket(),
        );

        $this->bus::chain($list)->dispatch();
    }
}
