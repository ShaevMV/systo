<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Application\ChangeOrderPrice;

use DomainException;
use Shared\Domain\Bus\Command\CommandHandler;
use Tickets\Order\OrderTicket\Repositories\OrderTicketRepositoryInterface;

class ChangeOrderPriceCommandHandler implements CommandHandler
{
    public function __construct(
        private OrderTicketRepositoryInterface $orderTicketRepository,
    ) {
    }

    /**
     * @throws DomainException
     */
    public function __invoke(ChangeOrderPriceCommand $command): void
    {
        $orderTicketDto = $this->orderTicketRepository->findOrder($command->getOrderId());
        if (is_null($orderTicketDto)) {
            throw new DomainException('Заказ не найден: ' . $command->getOrderId());
        }

        if ($command->getPrice() <= 0) {
            throw new DomainException('Цена должна быть больше нуля');
        }

        $this->orderTicketRepository->changePrice(
            $command->getOrderId(),
            $command->getPrice()
        );
    }
}
