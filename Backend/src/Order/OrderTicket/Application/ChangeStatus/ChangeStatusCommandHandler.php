<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Application\ChangeStatus;

use Bus;
use DomainException;
use Illuminate\Validation\ValidationException;
use Shared\Domain\ValueObject\Uuid;
use Throwable;
use Tickets\History\Domain\ActorType;
use Tickets\History\Dto\SaveHistoryDto;
use Tickets\History\Repositories\HistoryRepositoryInterface;
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
        private HistoryRepositoryInterface     $historyRepository,
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
                $orderTicketDto->getTicketTypeId() !== null
                && $orderTicketDto->getTicketTypeId()->equals(new Uuid('222abc0c-fc8e-4a1d-a4b0-d345cafada08')) ?
                    $this->externalPromocode->getPromocodeByOrderId($command->getOrderId()) :
                    null,
            ),
            //Status::PAID_FOR_LIVE => OrderTicket::toPaidInLiveTicket($orderTicketDto),
            Status::CANCEL => OrderTicket::toCancel($orderTicketDto),
            Status::CANCEL_FOR_LIVE => OrderTicket::toCancelLive($orderTicketDto),
            Status::LIVE_TICKET_ISSUED => OrderTicket::toLiveIssued($orderTicketDto, $command->getLiveNumber()),
            Status::DIFFICULTIES_AROSE => OrderTicket::toDifficultiesArose($orderTicketDto, $command->getComment()),
            Status::APPROVE_LIST => OrderTicket::toApproveList($orderTicketDto),
            Status::CANCEL_LIST  => OrderTicket::toCancelList($orderTicketDto),
            Status::DIFFICULTIES_AROSE_LIST => OrderTicket::toDifficultiesAroseList($orderTicketDto, $command->getComment()),
            default => throw new DomainException('Некорректный статус ' . $command->getNextStatus()),
        };

        if ($command->getNextStatus()->isDifficultiesArose()
            || $command->getNextStatus()->isDifficultiesAroseList()
        ) {
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

        $actorType = $command->getActorType();
        $actorId   = $actorType === ActorType::USER ? $command->getUserId()->value() : null;

        foreach ($orderTicket->pullHistoryEvents() as $historyEvent) {
            $this->historyRepository->save(new SaveHistoryDto(
                aggregateId: $command->getOrderId()->value(),
                event:       $historyEvent,
                actorId:     $actorId,
                actorType:   $actorType,
            ));
        }

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
