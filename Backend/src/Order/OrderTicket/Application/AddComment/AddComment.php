<?php

declare(strict_types = 1);

namespace Tickets\Order\OrderTicket\Application\AddComment;

use Throwable;
use Tickets\Shared\Domain\ValueObject\Uuid;
use Tickets\Shared\Infrastructure\Bus\Command\InMemorySymfonyCommandBus;

final class AddComment
{
    private InMemorySymfonyCommandBus $commandBus;

    public function __construct(AddCommentCommandHandler $addCommentCommandHandler)
    {
        $this->commandBus = new InMemorySymfonyCommandBus([
            AddCommentCommand::class => $addCommentCommandHandler,
        ]);
    }

    /**
     * @throws Throwable
     */
    public function send(Uuid $orderId, Uuid $userId, string $message): void
    {
        $this->commandBus->dispatch(new AddCommentCommand($orderId,$userId, $message));
    }
}
