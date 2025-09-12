<?php

declare(strict_types = 1);

namespace Tickets\Order\OrderTicket\Application\Create;

use Illuminate\Support\Facades\Auth;
use Shared\Domain\ValueObject\Status;
use Shared\Domain\ValueObject\Uuid;
use Throwable;
use Tickets\Order\OrderTicket\Application\ChanceStatus\ChanceStatus;
use Tickets\Order\OrderTicket\Repositories\OrderTicketRepositoryInterface;

final class CreatingOrderCommandHandler
{
    public function __construct(
        private OrderTicketRepositoryInterface $orderTicket,
        private ChanceStatus $chanceStatus,
    ){
    }

    /**
     * @throws Throwable
     */
    public function __invoke(CreatingOrderCommand $command): void
    {
        $this->orderTicket->create($command->getOrderTicketDto());

        $this->chanceStatus->chance(
            $command->getOrderTicketDto()->getId(),
            new Status(Status::PAID),
            new Uuid(Auth::id()),
            null,
            false,
            10
        );
    }
}
