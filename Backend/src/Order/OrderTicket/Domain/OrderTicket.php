<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Domain;

use Exception;
use JsonException;
use Tickets\Order\OrderTicket\Dto\OrderTicket\OrderTicketDto;
use Tickets\Shared\Domain\Aggregate\AggregateRoot;
use Tickets\Shared\Domain\ValueObject\Uuid;
use Tickets\Ticket\CreateTickets\Domain\ProcessCreateTicket;

final class OrderTicket extends AggregateRoot
{
    private Uuid $id;

    public function __construct(
        ?Uuid $id,
        protected OrderTicketDto $orderTicketDto,
    ) {
        $this->id = !is_null($id) ? $id : Uuid::random();
        $this->orderTicketDto->setId($this->id);

    }

    /**
     * @throws JsonException
     */
    private static function fromOrderTicketDto(OrderTicketDto $orderTicketDto): self
    {
        return new self(
            $orderTicketDto->getId(),
            $orderTicketDto,
        );
    }


    /**
     * @throws Exception
     */
    public static function create(OrderTicketDto $orderTicketDto): self
    {
        $result = self::fromOrderTicketDto($orderTicketDto);

        $result->record(new ProcessUserNotificationNewOrderTicket(
                $orderTicketDto->getId(),
                $orderTicketDto->getUserId(),
                $orderTicketDto->getEmail(),
            )
        );

        return $result;
    }

    /**
     * @throws JsonException
     */
    public static function toPaid(OrderTicketDto $orderTicketDto): self
    {
        $result = self::fromOrderTicketDto($orderTicketDto);

        $result->record(new ProcessCreateTicket(
            $result->id,
            $orderTicketDto->getGuests(),
            $orderTicketDto->getEmail(),
        ));

        $result->record(new ProcessUserNotificationNewOrderTicket(
                $orderTicketDto->getId(),
                $orderTicketDto->getUserId(),
                $orderTicketDto->getEmail(),
            )
        );

        return $result;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getDto(): OrderTicketDto
    {
        return $this->orderTicketDto;
    }

}
