<?php

namespace Tickets\Order\OrderFriendly\Application\CreateOrder;

use Nette\Utils\JsonException;
use Throwable;
use Tickets\Order\OrderFriendly\Repositories\InMemoryMySqlOrderFriendlyRepository;
use Tickets\Shared\Domain\Bus\Command\CommandHandler;

class CreateOrderCommandHandler implements CommandHandler
{
    public function __construct(
        private InMemoryMySqlOrderFriendlyRepository $orderTicketRepository
    )
    {
    }

    /**
     * @throws Throwable
     * @throws JsonException
     */
    public function __invoke(CreateOrderCommand $command)
    {
        $this->orderTicketRepository->create($command->getOrderTicketDto());
    }
}
