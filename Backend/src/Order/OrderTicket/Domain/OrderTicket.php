<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Domain;

use Nette\Utils\JsonException;
use Tickets\Order\OrderTicket\Dto\OrderTicket\PriceDto;
use Tickets\Shared\Domain\Aggregate\AggregateRoot;
use Tickets\Shared\Domain\ValueObject\Status;
use Tickets\Shared\Domain\ValueObject\Uuid;
use Tickets\Ticket\CreateTickets\Domain\ProcessCancelTicket;
use Tickets\Ticket\CreateTickets\Domain\ProcessCreateTicket;


final class OrderTicket extends AggregateRoot
{
    /**
     * @param  Ticket[]  $ticket
     */
    public function __construct(
        protected Uuid $festival_id,
        protected Uuid $user_id,
        protected Uuid $types_of_payment_id,
        protected PriceDto $price,
        protected Status $status,
        protected array $ticket,
        protected Uuid $id,
        protected ?string $promo_code = null,
    ) {
    }



    private static function fromOrderTicketDto(OrderTicketDto $orderTicketDto): self
    {
        $tickets = [];
        foreach ($orderTicketDto->getTicket() as $guest) {
            $tickets[] = Ticket::fromState($guest);
        }

        if (0 === count($tickets)) {
            throw new \DomainException('В заказе нет билетов');
        }

        return new self(
            $orderTicketDto->getFestivalId(),
            $orderTicketDto->getUserId(),
            $orderTicketDto->getTypesOfPaymentId(),
            $orderTicketDto->getPriceDto(),
            $orderTicketDto->getStatus(),
            $tickets,
            $orderTicketDto->getId(),
            $orderTicketDto->getPromoCode(),
        );
    }


    public static function create(
        OrderTicketDto $orderTicketDto,
    ): self {
        $result = self::fromOrderTicketDto($orderTicketDto);

        $result->record(new ProcessUserNotificationNewOrderTicket(
                $result->getId(),
                $result->user_id,
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
            $result->getTicket(),
            $orderTicketDto->getEmail(),
        ));

        $result->record(new ProcessUserNotificationOrderPaid(
                $result->getId(),
                $orderTicketDto->getEmail(),
            )
        );

        return $result;
    }

    /**
     * @throws JsonException
     */
    public static function toCancel(OrderTicketDto $orderTicketDto): self
    {
        $result = self::fromOrderTicketDto($orderTicketDto);

        $result->record(new ProcessCancelTicket(
            $result->id,
        ));

        $result->record(new ProcessUserNotificationOrderCancel(
                $orderTicketDto->getId(),
                $orderTicketDto->getEmail(),
            )
        );

        return $result;
    }

    /**
     * @throws JsonException
     */
    public static function toDifficultiesArose(OrderTicketDto $orderTicketDto): self
    {
        $result = self::fromOrderTicketDto($orderTicketDto);

        $result->record(new ProcessCancelTicket(
            $result->id,
        ));

        $result->record(new ProcessUserNotificationOrderCancel(
                $orderTicketDto->getId(),
                $orderTicketDto->getEmail(),
            )
        );

        return $result;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    /**
     * @return Ticket[]
     */
    public function getTicket(): array
    {
        return $this->ticket;
    }

}
