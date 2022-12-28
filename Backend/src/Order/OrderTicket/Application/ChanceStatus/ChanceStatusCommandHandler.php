<?php

namespace Tickets\Ordering\OrderTicket\Application\ChanceStatus;

use Tickets\Ordering\OrderTicket\Repositories\OrderTicketRepositoryInterface;
use Tickets\Shared\Domain\Bus\Command\CommandHandler;

class ChanceStatusCommandHandler implements CommandHandler
{
    public function __construct(
        OrderTicketRepositoryInterface $orderTicketRepository
    ){
    }

    public function __invoke(ChanceStatusCommand $command)
    {

    }
}
