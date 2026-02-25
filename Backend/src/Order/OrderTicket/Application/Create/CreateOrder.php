<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Application\Create;

use Bus;
use DomainException;
use Illuminate\Support\Facades\DB;
use Shared\Infrastructure\Bus\Command\InMemorySymfonyCommandBus;
use Shared\Infrastructure\Bus\Query\InMemorySymfonyQueryBus;
use Throwable;
use Tickets\Order\OrderTicket\Application\AddOrderInInvite\AddOrderInInviteCommand;
use Tickets\Order\OrderTicket\Application\AddOrderInInvite\AddOrderInInviteCommandHandler;
use Tickets\Order\OrderTicket\Application\GetOrderList\ForUser\OrderIdQuery;
use Tickets\Order\OrderTicket\Application\GetOrderList\ForUser\OrderItemQueryHandler;
use Tickets\Order\OrderTicket\Domain\OrderTicket;
use Tickets\Order\OrderTicket\Dto\OrderTicket\OrderTicketDto;
use Tickets\Order\OrderTicket\Responses\OrderTicketItemResponse;

final class CreateOrder
{
    private InMemorySymfonyCommandBus $commandBus;
    private InMemorySymfonyQueryBus $queryBus;

    public function __construct(
        CreatingOrderCommandHandler    $creatingOrderCommandHandler,
        OrderItemQueryHandler          $itemQueryHandler,
        AddOrderInInviteCommandHandler $addOrderInInviteCommandHandler,
        private Bus                    $bus,
    )
    {
        $this->commandBus = new InMemorySymfonyCommandBus([
            CreatingOrderCommand::class => $creatingOrderCommandHandler,
            AddOrderInInviteCommand::class => $addOrderInInviteCommandHandler,
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
        DB::beginTransaction();
        try {

            $this->commandBus->dispatch(new CreatingOrderCommand($orderTicketDto));

            /** @var OrderTicketItemResponse $orderTicketItem */
            $orderTicketItem = $this->queryBus->ask(new OrderIdQuery($orderTicketDto->getId()));
            if (is_null($orderTicketItem)) {
                throw new DomainException('Не получены данные о заказе ' . $orderTicketDto->getId()->value());
            }

            if(null !== $orderTicketDto->getInviteLink()) {
                $this->commandBus->dispatch(new AddOrderInInviteCommand(
                    $orderTicketDto->getInviteLink(),
                    $orderTicketDto->getId(),
                ));
            }

            $orderTicket = OrderTicket::create($orderTicketDto, $orderTicketItem->getKilter());

            $this->bus::chain($orderTicket->pullDomainEvents())
                ->dispatch();

            DB::commit();
        } catch (Throwable $throwable) {
            DB::rollBack();
            throw $throwable;
        }

        return true;
    }
}
