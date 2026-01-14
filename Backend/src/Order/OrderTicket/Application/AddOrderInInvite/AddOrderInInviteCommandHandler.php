<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Application\AddOrderInInvite;

use Shared\Domain\Bus\Command\CommandHandler;
use Tickets\Order\OrderTicket\Repositories\InviteLinkRepositoryInterface;

class AddOrderInInviteCommandHandler implements CommandHandler
{
    public function __construct(
        private InviteLinkRepositoryInterface $repository
    )
    {
    }


    public function __invoke(AddOrderInInviteCommand $command): void
    {
        $this->repository->addOrderInInviteLink(
            $command->getId(),
            $command->getOrderId(),
        );
    }
}
