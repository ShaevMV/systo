<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Application\ChangeStatus;

use DomainException;
use Throwable;
use Shared\Domain\ValueObject\Status;
use Shared\Domain\ValueObject\Uuid;
use Shared\Infrastructure\Bus\Command\InMemorySymfonyCommandBus;
use Tickets\History\Domain\ActorType;

class ChangeStatus
{
    private InMemorySymfonyCommandBus $commandBus;

    public function __construct(ChangeStatusCommandHandler $commandHandler)
    {
        $this->commandBus = new InMemorySymfonyCommandBus([
            ChangeStatusCommand::class => $commandHandler
        ]);
    }

    /**
     * @throws Throwable
     */
    public function change(
        Uuid    $orderId,
        Status  $status,
        Uuid    $userId,
        ?string $comment = null,
        bool    $now = false,
        int     $delay = 0,
        array   $liveList = [],
        string  $actorType = ActorType::USER,
    ): void
    {
        $this->commandBus->dispatch(new ChangeStatusCommand(
            $orderId,
            $status,
            $userId,
            $comment,
            $now,
            $delay,
            $liveList,
            $actorType,
        ));
    }
}
