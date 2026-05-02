<?php

declare(strict_types=1);

namespace Tickets\Orders\Friendly\Application\Create;

use Shared\Domain\Bus\Command\CommandHandler;
use Throwable;
use Tickets\History\Domain\ActorType;
use Tickets\Orders\Shared\Facade\OrderFacade;

final class CreateFriendlyOrderCommandHandler implements CommandHandler
{
    public function __construct(
        private readonly OrderFacade $facade,
    ) {}

    /**
     * @throws Throwable
     */
    public function __invoke(CreateFriendlyOrderCommand $command): void
    {
        $this->facade->createFriendly(
            dto:       $command->dto,
            actorId:   $command->actorId,
            actorType: ActorType::USER,
        );
    }
}
