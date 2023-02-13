<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Application\Create;

use Bus;
use DomainException;
use Throwable;
use Tickets\Order\OrderTicket\Application\GetOrderList\ForUser\OrderIdQuery;
use Tickets\Order\OrderTicket\Application\GetOrderList\ForUser\OrderItemQueryHandler;
use Tickets\Order\OrderTicket\Domain\OrderTicket;
use Tickets\Order\OrderTicket\Domain\OrderTicketDto;
use Tickets\Order\OrderTicket\Responses\OrderTicketItemResponse;
use Tickets\Shared\Infrastructure\Bus\Command\InMemorySymfonyCommandBus;
use Tickets\Shared\Infrastructure\Bus\Query\InMemorySymfonyQueryBus;

final class CreateOrder
{
    private InMemorySymfonyCommandBus $commandBus;
    private InMemorySymfonyQueryBus $queryBus;

    public function __construct(
        CreatingOrderCommandHandler $creatingOrderCommandHandler,
        OrderItemQueryHandler       $itemQueryHandler,
        private Bus                 $bus,
    )
    {
        $this->commandBus = new InMemorySymfonyCommandBus([
            CreatingOrderCommand::class => $creatingOrderCommandHandler
        ]);

        $this->queryBus = new InMemorySymfonyQueryBus([
            OrderIdQuery::class => $itemQueryHandler,
        ]);
    }

    /**
     * @throws Throwable
     */
    public function createAndSave(
        OrderTicketDto $orderTicketDto,
    ): bool
    {
        $this->commandBus->dispatch(new CreatingOrderCommand($orderTicketDto));

        /** @var OrderTicketItemResponse $orderTicketItem */
        $orderTicketItem = $this->queryBus->ask(new OrderIdQuery($orderTicketDto->getId()));
        if (is_null($orderTicketItem)) {
            throw new DomainException('Не получины данные о заказе ' . $orderTicketDto->getId()->value());
        }

        $orderTicket = OrderTicket::create($orderTicketDto, $orderTicketItem->getKilter());

        $this->bus::chain($orderTicket->pullDomainEvents())
            ->dispatch();

        return true;
    }
}
