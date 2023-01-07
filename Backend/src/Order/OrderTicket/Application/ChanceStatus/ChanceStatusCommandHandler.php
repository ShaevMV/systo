<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Application\ChanceStatus;

use Bus;
use DomainException;
use JsonException;
use Tickets\Order\OrderTicket\Domain\OrderTicket;
use Tickets\Order\OrderTicket\Repositories\OrderTicketRepositoryInterface;
use Tickets\Shared\Domain\Bus\Command\CommandHandler;

class ChanceStatusCommandHandler implements CommandHandler
{
    public function __construct(
        private OrderTicketRepositoryInterface $orderTicketRepository,
        private Bus $bus
    ) {
    }

    /**
     * @throws JsonException
     */
    public function __invoke(ChanceStatusCommand $command)
    {
        $orderTicketDto = $this->orderTicketRepository->findOrder($command->getOrderId());
        if (is_null($orderTicketDto)) {
            throw new DomainException('Не найден заказ '.$command->getOrderId());
        }

        if (!$orderTicketDto->getStatus()->isCorrectNextStatus($command->getNextStatus())) {
            throw new DomainException("Из текущего статуса {$orderTicketDto->getStatus()->getHumanStatus()}
            нельзя перевести в статус ".$command->getNextStatus()->getHumanStatus());
        }


        if ($command->getNextStatus()->isPaid()) {
            $orderTicket = OrderTicket::toPaid($orderTicketDto);
            $list = $orderTicket->pullDomainEvents();
            $this->bus::chain($list)->dispatch();
        }

        if ($command->getNextStatus()->isCancel()) {
            $orderTicket = OrderTicket::toCancel($orderTicketDto);
            $list = $orderTicket->pullDomainEvents();
            $this->bus::chain($list)->dispatch();
        }

        if ($command->getNextStatus()->isdDifficultiesArose()) {
            $orderTicket = OrderTicket::toDifficultiesArose($orderTicketDto);
            $list = $orderTicket->pullDomainEvents();
            $this->bus::chain($list)->dispatch();
        }


        $this->orderTicketRepository->chanceStatus(
            $command->getOrderId(),
            $command->getNextStatus()
        );
    }
}
