<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Application\Edit;

use Shared\Domain\Bus\Command\CommandHandler;
use Tickets\Order\OrderTicket\Repositories\FestivalRepositoryInterface;

class FestivalEditCommandHandler implements CommandHandler
{
    public function __construct(
        private FestivalRepositoryInterface $repository
    ) {
    }

    public function __invoke(FestivalEditCommand $command): void
    {
        $this->repository->editItem($command->getId(), $command->getData());
    }
}
