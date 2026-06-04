<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Application\ChangeOrderPrice;

use DomainException;
use Illuminate\Support\Facades\DB;
use Shared\Domain\Bus\Command\CommandHandler;
use Tickets\History\Domain\Event\OrderPriceChangedEvent;
use Tickets\History\Dto\SaveHistoryDto;
use Tickets\History\Repositories\HistoryRepositoryInterface;
use Tickets\Order\OrderTicket\Repositories\OrderTicketRepositoryInterface;

class ChangeOrderPriceCommandHandler implements CommandHandler
{
    public function __construct(
        private OrderTicketRepositoryInterface $orderTicketRepository,
        private HistoryRepositoryInterface     $historyRepository,
    ) {
    }

    /**
     * @throws DomainException
     * @throws \Throwable
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

        // Старая цена для истории — денормализованный итог по строкам заказа.
        // changePrice() (deprecated) меняет только order-level колонку price, не трогая
        // per-guest price_snapshot — полноценный per-guest перерасчёт запланирован в v2.7.0.
        $fromPrice = $orderTicketDto->totalPrice()->asFloat();
        $toPrice   = (float)$command->getPrice();

        // Атомарно: смена цены + запись в domain_history. История пишется
        // ровно в одном месте — этом handler — чтобы исключить задвоение между
        // разными вызовами changePrice (контроллер /order/changePrice, artisan-команды и т.п.).
        // Если caller хочет идемпотентность — он сам фильтрует на своей стороне.
        DB::transaction(function () use ($command, $fromPrice, $toPrice): void {
            $this->orderTicketRepository->changePrice(
                $command->getOrderId(),
                $toPrice
            );

            $this->historyRepository->save(new SaveHistoryDto(
                aggregateId: $command->getOrderId()->value(),
                event: new OrderPriceChangedEvent(
                    fromPrice: $fromPrice,
                    toPrice:   $toPrice,
                    reason:    $command->getReason(),
                ),
                actorId:   $command->getAdminId()?->value(),
                actorType: $command->getActorType(),
            ));
        });
    }
}
