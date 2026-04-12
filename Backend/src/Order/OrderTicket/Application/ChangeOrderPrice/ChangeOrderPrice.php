<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Application\ChangeOrderPrice;

use Shared\Domain\ValueObject\Uuid;
use Shared\Infrastructure\Bus\Command\InMemorySymfonyCommandBus;
use Throwable;

class ChangeOrderPrice
{
    private InMemorySymfonyCommandBus $commandBus;

    public function __construct(ChangeOrderPriceCommandHandler $commandHandler)
    {
        $this->commandBus = new InMemorySymfonyCommandBus([
            ChangeOrderPriceCommand::class => $commandHandler
        ]);
    }

    /**
     * @throws Throwable
     */
    public function change(Uuid $orderId, float $price, Uuid $adminId): void
    {
        $this->commandBus->dispatch(new ChangeOrderPriceCommand(
            $orderId,
            $price,
            $adminId
        ));
    }
}
