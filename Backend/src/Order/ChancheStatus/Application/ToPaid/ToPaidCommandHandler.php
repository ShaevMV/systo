<?php

namespace Tickets\Order\ChancheStatus\Application\ToPaid;

use Nette\Utils\JsonException;
use Tickets\Order\OrderTicket\Domain\OrderTicket;
use Tickets\Order\OrderTicket\Dto\OrderTicket\OrderTicketDto;
use Tickets\Order\OrderTicket\Repositories\OrderTicketRepositoryInterface;
use Tickets\Shared\Domain\Bus\Command\CommandHandler;

class ToPaidCommandHandler implements CommandHandler
{
    public function __construct(
        private OrderTicketRepositoryInterface $orderTicketRepository,
    ) {
    }


    /**
     * @throws JsonException
     * @throws \JsonException
     */
    public function __invoke(ToPaidCommand $paidCommand)
    {
        $orderTicketItem = $this->orderTicketRepository->findOrder($paidCommand->getOrderId());
        if(null === $orderTicketItem) {
            throw new \DomainException('Ненашел заказ '. $paidCommand->getOrderId());
        }
        $orderTicket = OrderTicket::toPaid(OrderTicketDto::fromState($orderTicketItem->toArray()));


    }
}
