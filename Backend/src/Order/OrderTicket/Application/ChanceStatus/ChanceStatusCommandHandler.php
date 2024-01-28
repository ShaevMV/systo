<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Application\ChanceStatus;

use Bus;
use DomainException;
use Illuminate\Validation\ValidationException;
use JsonException;
use Throwable;
use Tickets\Order\OrderTicket\Application\AddComment\AddComment;
use Tickets\Order\OrderTicket\Domain\OrderTicket;
use Tickets\Order\OrderTicket\Repositories\OrderTicketRepositoryInterface;
use Shared\Domain\Bus\Command\CommandHandler;
use Shared\Domain\ValueObject\Status;
use Tickets\Ticket\CreateTickets\Application\PushTicket;

class ChanceStatusCommandHandler implements CommandHandler
{
    public function __construct(
        private OrderTicketRepositoryInterface $orderTicketRepository,
        private Bus                            $bus,
        private AddComment                     $addComment,
        private PushTicket                     $pushTicket,
    )
    {
    }

    /**
     * @throws ValidationException
     * @throws Throwable
     */
    public function __invoke(ChanceStatusCommand $command)
    {
        $orderTicketDto = $this->orderTicketRepository->findOrder($command->getOrderId());
        if (is_null($orderTicketDto)) {
            throw new DomainException('Не найден заказ ' . $command->getOrderId());
        }

        if (!$orderTicketDto->getStatus()->isCorrectNextStatus($command->getNextStatus())) {
            throw new DomainException("Из текущего статуса {$orderTicketDto->getStatus()->getHumanStatus()}
            нельзя перевести в статус " . $command->getNextStatus()->getHumanStatus());
        }

        $orderTicket = match ((string)$command->getNextStatus()) {
            Status::PAID => OrderTicket::toPaid($orderTicketDto),
            Status::CANCEL => OrderTicket::toCancel($orderTicketDto),
            Status::LIVE_TICKET_ISSUED => OrderTicket::toLiveIssued($orderTicketDto),
            Status::DIFFICULTIES_AROSE => OrderTicket::toDifficultiesArose($orderTicketDto, $command->getComment()),
            default => throw new DomainException('Некорректный статус ' . $command->getNextStatus()),
        };

        if ($command->getNextStatus()->isdDifficultiesArose()) {
            $this->addComment->send(
                $command->getOrderId(),
                $command->getUserId(),
                $command->getComment(),
            );
        }

        $list = $orderTicket->pullDomainEvents();

        $this->orderTicketRepository->chanceStatus(
            $command->getOrderId(),
            $command->getNextStatus(),
            $orderTicket->getTicket()
        );

        if($command->isNow()) {
            $this->bus::chain($list)->onConnection('sync')->dispatch();
        } else {
            $this->bus::chain($list)->dispatch();

        }

        $this->pushTicket->pushByOrderId($command->getOrderId());
    }
}
