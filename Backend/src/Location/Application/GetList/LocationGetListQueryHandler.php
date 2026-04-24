<?php

declare(strict_types=1);

namespace Tickets\Location\Application\GetList;

use Shared\Domain\Bus\Query\QueryHandler;
use Tickets\Location\Repository\LocationRepositoryInterface;
use Tickets\Location\Response\LocationGetListResponse;

class LocationGetListQueryHandler implements QueryHandler
{
    public function __construct(
        private LocationRepositoryInterface $repository,
    ) {
    }

    public function __invoke(LocationGetListQuery $query): LocationGetListResponse
    {
        return new LocationGetListResponse(
            $this->repository->getList(
                $query->getFilter(),
                $query->getOrderBy(),
            )
        );
    }
}
