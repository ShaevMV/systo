<?php

declare(strict_types=1);

namespace Tickets\TicketTypePrice\Application\GetItem;

use Shared\Domain\Bus\Query\QueryHandler;
use Tickets\TicketTypePrice\Dto\TicketTypePriceDto;
use Tickets\TicketTypePrice\Repositories\TicketTypePriceRepositoryInterface;

class TicketTypePriceGetItemQueryHandler implements QueryHandler
{
    public function __construct(
        private TicketTypePriceRepositoryInterface $repository
    ) {
    }

    public function __invoke(TicketTypePriceGetItemQuery $query): TicketTypePriceDto
    {
        return $this->repository->getItem($query->getId());
    }
}
