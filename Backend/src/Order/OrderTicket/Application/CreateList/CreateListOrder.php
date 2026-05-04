<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Application\CreateList;

use Bus;
use DomainException;
use Illuminate\Support\Facades\DB;
use Shared\Infrastructure\Bus\Command\InMemorySymfonyCommandBus;
use Shared\Infrastructure\Bus\Query\InMemorySymfonyQueryBus;
use Throwable;
use Tickets\History\Domain\ActorType;
use Tickets\History\Dto\SaveHistoryDto;
use Tickets\History\Repositories\HistoryRepositoryInterface;
use Tickets\Order\OrderTicket\Application\Create\CreatingOrderCommand;
use Tickets\Order\OrderTicket\Application\Create\CreatingOrderCommandHandler;
use Tickets\Order\OrderTicket\Application\GetOrderList\ForUser\OrderIdQuery;
use Tickets\Order\OrderTicket\Application\GetOrderList\ForUser\OrderItemQueryHandler;
use Tickets\Order\OrderTicket\Domain\OrderTicket;
use Tickets\Order\OrderTicket\Dto\OrderTicket\OrderTicketDto;
use Tickets\Order\OrderTicket\Responses\OrderTicketItemResponse;

/**
 * Создание заказа-списка куратором.
 *
 * Аналог CreateOrder::createAndSave, но:
 * - Использует фабрику OrderTicket::createList (статус NEW_LIST)
 * - НЕ генерирует ProcessUserNotificationNewOrderTicket — пользователь и куратор писем при создании не получают
 * - Domain Events ограничены только записью в историю (письмо придёт при APPROVE_LIST через смену статуса)
 */
final class CreateListOrder
{
    private InMemorySymfonyCommandBus $commandBus;
    private InMemorySymfonyQueryBus $queryBus;

    public function __construct(
        CreatingOrderCommandHandler        $creatingOrderCommandHandler,
        OrderItemQueryHandler              $itemQueryHandler,
        private Bus                        $bus,
        private HistoryRepositoryInterface $historyRepository,
    ) {
        $this->commandBus = new InMemorySymfonyCommandBus([
            CreatingOrderCommand::class => $creatingOrderCommandHandler,
        ]);

        $this->queryBus = new InMemorySymfonyQueryBus([
            OrderIdQuery::class => $itemQueryHandler,
        ]);
    }

    /**
     * @throws Throwable
     */
    public function createAndSave(OrderTicketDto $orderTicketDto): bool
    {
        DB::beginTransaction();
        try {
            $this->commandBus->dispatch(new CreatingOrderCommand($orderTicketDto));

            /** @var OrderTicketItemResponse|null $orderTicketItem */
            $orderTicketItem = $this->queryBus->ask(new OrderIdQuery($orderTicketDto->getId()));
            if (is_null($orderTicketItem)) {
                throw new DomainException('Не получены данные о заказе ' . $orderTicketDto->getId()->value());
            }

            $orderTicket = OrderTicket::createList($orderTicketDto, $orderTicketItem->getKilter());

            foreach ($orderTicket->pullHistoryEvents() as $historyEvent) {
                $this->historyRepository->save(new SaveHistoryDto(
                    aggregateId: $orderTicketDto->getId()->value(),
                    event:       $historyEvent,
                    actorId:     $orderTicketDto->getCuratorId()?->value(),
                    actorType:   ActorType::USER,
                ));
            }

            $domainEvents = $orderTicket->pullDomainEvents();
            if (! empty($domainEvents)) {
                $this->bus::chain($domainEvents)->dispatch();
            }

            DB::commit();
        } catch (Throwable $throwable) {
            DB::rollBack();
            throw $throwable;
        }

        return true;
    }
}
