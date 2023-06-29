<?php

declare(strict_types=1);

namespace Tickets\Order\InfoForOrder\Application\GetInfoForOrder;

use Tickets\Order\InfoForOrder\Response\InfoForOrderingDto;
use Tickets\Shared\Domain\ValueObject\Uuid;
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

    public function getInfo(Uuid $festivalId): InfoForOrderingDto
    {
        /** @var InfoForOrderingDto $result */
        $result = $this->queryBus->ask(new GetAllInfoForOrderQuery($festivalId));

        return $result;
    }
}
