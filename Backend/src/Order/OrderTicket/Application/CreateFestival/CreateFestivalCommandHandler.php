<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Application\CreateFestival;

use Shared\Domain\Bus\Command\CommandHandler;
use Tickets\Order\OrderTicket\Repositories\FestivalRepositoryInterface;

class CreateFestivalCommandHandler implements CommandHandler
{
    public function __construct(
        private FestivalRepositoryInterface $repository
    ) {
    }

    public function __invoke(CreateFestivalCommand $command): void
    {
        $this->repository->create($command->getData());
    }
}
