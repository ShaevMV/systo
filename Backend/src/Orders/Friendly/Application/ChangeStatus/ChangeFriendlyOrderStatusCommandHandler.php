<?php

declare(strict_types=1);

namespace Tickets\Orders\Friendly\Application\ChangeStatus;

use Shared\Domain\Bus\Command\CommandHandler;
use Throwable;
use Tickets\Orders\Shared\Facade\OrderFacade;

final class ChangeFriendlyOrderStatusCommandHandler implements CommandHandler
{
    public function __construct(
        private readonly OrderFacade $facade,
    ) {}

    /**
     * @throws Throwable
     */
    public function __invoke(ChangeFriendlyOrderStatusCommand $command): void
    {
        $this->facade->changeFriendlyStatus(
            orderId:   $command->orderId,
            newStatus: $command->newStatus,
            params:    $command->params,
            actorId:   $command->actorId,
            actorType: $command->actorType,
        );
    }
}
