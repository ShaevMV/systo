<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Application\ChanceStatus;

use DomainException;
use Throwable;
use Tickets\Shared\Domain\ValueObject\Status;
use Tickets\Shared\Domain\ValueObject\Uuid;
use Tickets\Shared\Infrastructure\Bus\Command\InMemorySymfonyCommandBus;

class ChanceStatus
{
    private InMemorySymfonyCommandBus $commandBus;

    public function __construct(ChanceStatusCommandHandler $commandHandler)
    {
        $this->commandBus = new InMemorySymfonyCommandBus([
            ChanceStatusCommand::class => $commandHandler
        ]);
    }

    /**
     * @throws Throwable
     */
    public function chance(Uuid $orderId, Status $status): void
    {
        $this->commandBus->dispatch(new ChanceStatusCommand(
            $orderId,
            $status
        ));
    }
}
