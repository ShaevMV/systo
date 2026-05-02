<?php

declare(strict_types=1);

namespace Tickets\Orders\Live\Application\Create;

use Shared\Domain\Bus\Command\CommandHandler;
use Throwable;
use Tickets\History\Domain\ActorType;
use Tickets\Orders\Shared\Facade\OrderFacade;

final class CreateLiveOrderCommandHandler implements CommandHandler
{
    public function __construct(
        private readonly OrderFacade $facade,
    ) {}

    /**
     * @throws Throwable
     */
    public function __invoke(CreateLiveOrderCommand $command): void
    {
        $this->facade->createLive(
            dto:       $command->dto,
            actorType: ActorType::SYSTEM,
        );
    }
}
