<?php

declare(strict_types=1);

namespace Baza\Festival\Applications\ListFestivals;

use Baza\Festival\Repositories\FestivalRepositoryInterface;
use Baza\Shared\Domain\Bus\Query\QueryHandler;

class ListFestivalsQueryHandler implements QueryHandler
{
    public function __construct(
        private FestivalRepositoryInterface $repository,
    ) {
    }

    public function __invoke(ListFestivalsQuery $query): FestivalListResponse
    {
        return new FestivalListResponse(
            $query->onlyActiveForKpp()
                ? $this->repository->listActiveForKpp()
                : $this->repository->all()
        );
    }
}
