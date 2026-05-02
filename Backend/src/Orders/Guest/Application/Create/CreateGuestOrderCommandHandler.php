<?php

declare(strict_types=1);

namespace Tickets\Orders\Guest\Application\Create;

use Shared\Domain\Bus\Command\CommandHandler;
use Throwable;
use Tickets\History\Domain\ActorType;
use Tickets\Orders\Shared\Facade\OrderFacade;

final class CreateGuestOrderCommandHandler implements CommandHandler
{
    public function __construct(
        private readonly OrderFacade $facade,
    ) {}

    /**
     * @throws Throwable
     */
    public function __invoke(CreateGuestOrderCommand $command): void
    {
        $this->facade->createGuest(
            dto:       $command->dto,
            actorType: ActorType::SYSTEM,
        );
    }
}
