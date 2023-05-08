<?php

declare(strict_types=1);

namespace Baza\Changes\Applications\Report;

use Baza\Changes\Repositories\ChangesRepositoryInterface;
use Baza\Shared\Domain\Bus\Query\QueryHandler;

class ReportForChangesQueryHandler implements QueryHandler
{
    public function __construct(
        private ChangesRepositoryInterface $repository
    )
    {
    }

    public function __invoke(ReportForChangesQuery $query): ReportForChangesResponse
    {
        return new ReportForChangesResponse($this->repository->getAllReport());
    }
}
