<?php

declare(strict_types=1);

namespace Tickets\Location\Application\GetItem;

use Shared\Domain\Bus\Query\QueryHandler;
use Tickets\Location\Dto\LocationDto;
use Tickets\Location\Repository\LocationRepositoryInterface;

class LocationGetItemQueryHandler implements QueryHandler
{
    public function __construct(
        private LocationRepositoryInterface $repository,
    ) {
    }

    public function __invoke(LocationGetItemQuery $query): LocationDto
    {
        return $this->repository->getItem($query->getId());
    }
}
