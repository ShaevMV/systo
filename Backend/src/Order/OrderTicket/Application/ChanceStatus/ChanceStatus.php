<?php

namespace Tickets\Order\OrderTicket\Application\ChanceStatus;

use Tickets\Shared\Infrastructure\Bus\Command\InMemorySymfonyCommandBus;

class ChanceStatus
{
    private InMemorySymfonyCommandBus $commandBus;

    public function __construct(private ChanceStatusCommandHandler $commandHandler)
    {
        $this->commandBus = new InMemorySymfonyCommandBus([

        ]);
    }
}
