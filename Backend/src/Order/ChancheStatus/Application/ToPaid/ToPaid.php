<?php

namespace Tickets\Order\ChancheStatus\Application\ToPaid;

use Throwable;
use Tickets\Shared\Domain\ValueObject\Uuid;
use Tickets\Shared\Infrastructure\Bus\Command\InMemorySymfonyCommandBus;

class ToPaid
{
    private InMemorySymfonyCommandBus $commandBus;

    public function __construct(
        private ToPaidCommandHandler $commandHandler
    ){
        $this->commandBus = new InMemorySymfonyCommandBus([
            ToPaidCommand::class => $this->commandHandler
        ]);
    }


    /**
     * @throws Throwable
     */
    public function shared(Uuid $orderId): void
    {
        $this->commandBus->dispatch(new ToPaidCommand($orderId));
    }

}
