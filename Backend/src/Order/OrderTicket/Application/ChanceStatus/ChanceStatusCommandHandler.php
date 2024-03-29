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
use Tickets\Shared\Domain\Bus\Command\CommandHandler;
use Tickets\Shared\Domain\ValueObject\Status;

class ChanceStatusCommandHandler implements CommandHandler
{
    public function __construct(
        private OrderTicketRepositoryInterface $orderTicketRepository,
        private Bus                            $bus,
        private AddComment                     $addComment,
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
            Status::DIFFICULTIES_AROSE => OrderTicket::toDifficultiesArose($orderTicketDto, $command->getComment()),
            default => throw new DomainException('Не коректнный статус' . $command->getNextStatus()),
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

        $this->bus::chain($list)->dispatch();
    }
}
