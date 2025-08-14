<?php

declare(strict_types=1);


namespace Tickets\Billing\Application;

use Shared\Domain\ValueObject\Uuid;
use Shared\Infrastructure\Bus\Command\InMemorySymfonyCommandBus;
use Tickets\Billing\Application\WebHook\WebHookCommand;
use Tickets\Billing\Application\WebHook\WebHookCommandHandler;
use Tickets\Billing\ValueObject\StatusForBillingValueObject;

class Billing
{
    private InMemorySymfonyCommandBus $commandBus;

    public function __construct(WebHookCommandHandler $commandHandler)
    {
        $this->commandBus = new InMemorySymfonyCommandBus([
            WebHookCommand::class => $commandHandler
        ]);
    }


    /**
     * @throws \Throwable
     */
    public function webHook(
        string $orderId,
        string $status,
        ?string $linkToReceipt = null,
    ): void
    {
        $this->commandBus->dispatch(new WebHookCommand(
            new Uuid($orderId),
            new StatusForBillingValueObject($status),
            $linkToReceipt
        ));
    }
}
