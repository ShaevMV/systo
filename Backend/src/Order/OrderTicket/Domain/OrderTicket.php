<?php

declare(strict_types=1);

namespace Tickets\Ordering\OrderTicket\Domain;

use DateTime;
use Exception;
use Tickets\Ordering\OrderTicket\Dto\OrderTicketDto;
use Tickets\Shared\Domain\Aggregate\AggregateRoot;
use Tickets\Shared\Domain\ValueObject\Status;
use Tickets\Shared\Domain\ValueObject\Uuid;

final class OrderTicket extends AggregateRoot
{
    public function __construct(
        protected Uuid $id,
        protected array $guests,
        protected DateTime $date,
        protected Uuid $idBuy,
        protected Status $status,
        protected ?string $promoCod = null,
    ) {
    }

    /**
     * @throws Exception
     */
    public static function createFromOrderTicketDto(OrderTicketDto $orderTicketDto, string $email): self
    {
        $result = new self(
            $orderTicketDto->getId(),
            $orderTicketDto->getGuestsToArray(),
            $orderTicketDto->getDate(),
            $orderTicketDto->getTypesOfPaymentId(),
            $orderTicketDto->getStatus(),
            $orderTicketDto->getPromoCode()
        );

        $result->record(new ProcessUserNotificationNewOrderTicket($email,$orderTicketDto->getId()));

        return $result;
    }

    public static function recoverFromState(array $data): self
    {

    }


    public function chanceStatus(Status $nextStatus): self
    {
        if(!$this->status->isCorrectNextStatus($nextStatus)) {
            throw new \DomainException('Не корректный статус!!!');
        }

        $this->status = $nextStatus;

        $this->record(new ProcessUserNotificationNewOrderTicket($email,$orderTicketDto->getId()));

        return $this;
    }
}
