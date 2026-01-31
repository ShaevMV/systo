<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Bus\Command;

use Symfony\Component\Messenger\Stamp\HandledStamp;
use Throwable;
use Shared\Domain\Bus\Command\Command;
use Shared\Domain\Bus\Command\CommandBus;
use Shared\Infrastructure\Bus\CallableFirstParameterExtractor;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\Exception\NoHandlerForMessageException;
use Symfony\Component\Messenger\Handler\HandlersLocator;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\Middleware\HandleMessageMiddleware;

final class InMemorySymfonyCommandBus implements CommandBus
{
    private MessageBus $bus;

    public function __construct(iterable $commandHandlers)
    {
        $this->bus = new MessageBus(
            [
                new HandleMessageMiddleware(
                    new HandlersLocator(CallableFirstParameterExtractor::forCallables($commandHandlers))
                ),
            ]
        );
    }

    /**
     * @throws Throwable
     */
    public function dispatch(Command $command): ?CommandResponse
    {
        try {
            /** @var HandledStamp $stamp */
            $stamp = $this->bus->dispatch($command)->last(HandledStamp::class);;
            return $stamp->getResult();
        } catch (NoHandlerForMessageException) {
            throw new CommandNotRegisteredError($command);
        } catch (HandlerFailedException $error) {
            throw $error->getPrevious() ?? $error;
        }
    }
}
