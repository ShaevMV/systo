<?php

namespace Baza\Tickets\Applications\Enter\DrugTicket;

use Baza\Shared\Domain\Bus\Command\CommandHandler;
use Baza\Tickets\Repositories\ElTicketsRepositoryInterface;
use Baza\Tickets\Repositories\FriendlyTicketRepositoryInterface;
use Baza\Tickets\Repositories\SpisokTicketsRepositoryInterface;
use Symfony\Component\HttpKernel\Fragment\FragmentRendererInterface;

class DrugTicketCommandHandler implements CommandHandler
{
    public function __construct(
        private FriendlyTicketRepositoryInterface $repository
    )
    {
    }

    public function __invoke(DrugTicketCommand $command): void
    {
        $this->repository->skip($command->getId(), $command->getUserId());
    }
}
