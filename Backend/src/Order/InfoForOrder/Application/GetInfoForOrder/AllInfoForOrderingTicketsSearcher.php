<?php

declare(strict_types=1);

namespace Tickets\Ordering\InfoForOrder\Application\GetInfoForOrder;

use Tickets\Ordering\InfoForOrder\Response\InfoForOrderingDto;
use Tickets\Shared\Infrastructure\Bus\Query\InMemorySymfonyQueryBus;

final class AllInfoForOrderingTicketsSearcher
{
    private InMemorySymfonyQueryBus $queryBus;

    public function __construct(GetAllInfoForOrderQueryHandler $getAllInfoForOrderQueryHandler)
    {
        $this->queryBus = new InMemorySymfonyQueryBus([
            GetAllInfoForOrderQuery::class => $getAllInfoForOrderQueryHandler
        ]);
    }

    public function getInfo(): InfoForOrderingDto
    {
        /** @var InfoForOrderingDto $result */
        $result = $this->queryBus->ask(new GetAllInfoForOrderQuery());

        return $result;
    }
}
