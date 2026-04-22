<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Application\ChangeStatus;

use Bus;
use DomainException;
use Illuminate\Validation\ValidationException;
use Shared\Domain\ValueObject\Uuid;
use Throwable;
use Tickets\Order\OrderTicket\Application\AddComment\AddComment;
use Tickets\Order\OrderTicket\Domain\OrderTicket;
use Tickets\Order\OrderTicket\Repositories\OrderTicketRepositoryInterface;
use Shared\Domain\Bus\Command\CommandHandler;
use Shared\Domain\ValueObject\Status;
use Tickets\PromoCode\Application\ExternalPromocode\ExternalPromocode;
use Tickets\Ticket\CreateTickets\Application\PushTicket;

class ChangeStatusCommandHandler implements CommandHandler
{
    public function __construct(
        private OrderTicketRepositoryInterface $orderTicketRepository,
        private Bus                            $bus,
        private AddComment                     $addComment,
        private PushTicket                     $pushTicket,
        private ExternalPromocode              $externalPromocode,
    )
    {
    }

    /**
     * @throws ValidationException
     * @throws Throwable
     */
    public function __invoke(ChangeStatusCommand $command)
    {
        $orderTicketDto = $this->orderTicketRepository->findOrder($command->getOrderId());
        if (is_null($orderTicketDto)) {
            throw new DomainException('Не найден заказ ' . $command->getOrderId());
        }

        // Проверка idempotency: если заказ уже в целевом статусе — это повторный запрос
        if ($orderTicketDto->getStatus()->equals($command->getNextStatus())) {
            throw new DomainException("Заказ уже находится в статусе {$orderTicketDto->getStatus()->getHumanStatus()}");
        }

        if (!$orderTicketDto->getStatus()->isCorrectNextStatus($command->getNextStatus())) {
            throw new DomainException("Из текущего статуса {$orderTicketDto->getStatus()->getHumanStatus()}
            нельзя перевести в статус " . $command->getNextStatus()->getHumanStatus());
        }

        $orderTicket = match ((string)$command->getNextStatus()) {
            Status::PAID => OrderTicket::toPaid(
                $orderTicketDto,
                $command->getComment(),
                $orderTicketDto->getTicketTypeId()->equals(new Uuid('222abc0c-fc8e-4a1d-a4b0-d345cafada08')) ?
                    $this->externalPromocode->getPromocodeByOrderId($command->getOrderId()) :
                    null,
            ),
            //Status::PAID_FOR_LIVE => OrderTicket::toPaidInLiveTicket($orderTicketDto),
            Status::CANCEL => OrderTicket::toCancel($orderTicketDto),
            Status::CANCEL_FOR_LIVE => OrderTicket::toCancelLive($orderTicketDto),
            Status::LIVE_TICKET_ISSUED => OrderTicket::toLiveIssued($orderTicketDto, $command->getLiveNumber()),
            Status::DIFFICULTIES_AROSE => OrderTicket::toDifficultiesArose($orderTicketDto, $command->getComment()),
            default => throw new DomainException('Некорректный статус ' . $command->getNextStatus()),
        };

        if ($command->getNextStatus()->isDifficultiesArose()) {
            $this->addComment->send(
                $command->getOrderId(),
                $command->getUserId(),
                $command->getComment(),
            );
        }

        if ($command->getNextStatus()->isLiveIssued()) {
            $this->addComment->send(
                $command->getOrderId(),
                $command->getUserId(),
                implode(' ', $command->getLiveNumber()),
            );
        }

        $list = $orderTicket->pullDomainEvents();

        $this->orderTicketRepository->changeStatus(
            $command->getOrderId(),
            $command->getNextStatus(),
            $orderTicket->getTicket()
        );

        if ($command->isNow()) {
            $this->bus::chain($list)->onConnection('sync')->dispatch();
        } else {
            if ($command->getDelayMinute() > 0) {
                $this->bus::chain($list)
                    ->delay(
                        now()->addMinutes($command->getDelayMinute())
                    )->dispatch();
            } else {
                $this->bus::chain($list)->dispatch();
            }

        }

        $this->pushTicket->pushByOrderId($command->getOrderId());
    }
}
