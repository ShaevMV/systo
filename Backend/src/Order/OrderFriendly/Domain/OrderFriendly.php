<?php

declare(strict_types=1);

namespace Tickets\Order\OrderFriendly\Domain;

use Tickets\Order\OrderTicket\Domain\ProcessUserNotificationNewOrderTicket;
use Tickets\Order\OrderTicket\Domain\ProcessUserNotificationOrderCancel;
use Tickets\Order\OrderTicket\Domain\ProcessUserNotificationOrderDifficultiesArose;
use Tickets\Order\OrderTicket\Domain\ProcessUserNotificationOrderPaid;
use Tickets\Order\Shared\Dto\GuestsDto;
use Tickets\Order\Shared\Dto\PriceDto;
use Tickets\Shared\Domain\Aggregate\AggregateRoot;
use Tickets\Shared\Domain\ValueObject\Status;
use Tickets\Shared\Domain\ValueObject\Uuid;
use Tickets\Ticket\CreateTickets\Domain\ProcessCancelTicket;
use Tickets\Ticket\CreateTickets\Domain\ProcessCreateTicket;

class OrderFriendly extends AggregateRoot
{
    /**
     * @param  GuestsDto[]  $ticket
     */
    public function __construct(
        protected Uuid $festival_id,
        protected Uuid $user_id,
        protected PriceDto $price,
        protected Status $status,
        protected array $ticket,
        protected Uuid $id,
    ) {
    }



    private static function fromOrderTicketDto(OrderTicketDto $orderTicketDto): self
    {
        return new self(
            $orderTicketDto->getFestivalId(),
            $orderTicketDto->getUserId(),
            $orderTicketDto->getPriceDto(),
            $orderTicketDto->getStatus(),
            $orderTicketDto->getTicket(),
            $orderTicketDto->getId(),
        );
    }


    public static function create(
        OrderTicketDto $orderTicketDto,
        int $kilter,
    ): self {
        $result = self::fromOrderTicketDto($orderTicketDto);

        $result->record(new ProcessUserNotificationNewOrderTicket(
                $orderTicketDto->getEmail(),
                $kilter
            )
        );

        return $result;
    }

    public static function toPaid(OrderTicketDto $orderTicketDto): self
    {
        $result = self::fromOrderTicketDto($orderTicketDto);

        $result->record(new ProcessCreateTicket(
            $result->id,
            $result->getTicket()
        ));

        $result->record(new ProcessUserNotificationOrderPaid(
                $orderTicketDto->getEmail(),
                $result->getTicket(),
            )
        );

        return $result;
    }

    public static function toCancel(OrderTicketDto $orderTicketDto): self
    {
        $result = self::fromOrderTicketDto($orderTicketDto);
        $result->updateIdTicket();
        $result->record(new ProcessCancelTicket(
            $result->id,
        ));

        $result->record(new ProcessUserNotificationOrderCancel(
                $orderTicketDto->getEmail(),
            )
        );

        return $result;
    }

    private function updateIdTicket(): void
    {
        foreach ($this->ticket as &$guestsDto) {
            $guestsDto->updateId();
        }
    }

    public static function toDifficultiesArose(OrderTicketDto $orderTicketDto, ?string $comment): self
    {
        if(is_null($comment)) {
            throw new \DomainException('Комментарий обязательный для смены статус "Возникли трудности"');
        }

        $result = self::fromOrderTicketDto($orderTicketDto);
        $result->updateIdTicket();
        $result->record(new ProcessCancelTicket(
            $result->id,
        ));

        $result->record(new ProcessUserNotificationOrderDifficultiesArose(
                $orderTicketDto->getId(),
                $orderTicketDto->getEmail(),
                $comment
            )
        );

        return $result;
    }

    public function getTicket(): array
    {
        return $this->ticket;
    }
}
