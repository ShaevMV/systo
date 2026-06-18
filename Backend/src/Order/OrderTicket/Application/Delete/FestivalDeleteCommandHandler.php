<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Application\Delete;

use Shared\Domain\Bus\Command\CommandHandler;
use Tickets\Order\OrderTicket\Repositories\FestivalRepositoryInterface;

class FestivalDeleteCommandHandler implements CommandHandler
{
    public function __construct(
        private FestivalRepositoryInterface $repository
    ) {
    }

    public function __invoke(FestivalDeleteCommand $command): void
    {
        $this->repository->remove($command->getId());
    }
}
