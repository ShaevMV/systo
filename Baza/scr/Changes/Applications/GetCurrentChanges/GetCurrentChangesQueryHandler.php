<?php

declare(strict_types=1);

namespace Baza\Changes\Applications\GetCurrentChanges;

use Baza\Changes\Repositories\ChangesRepositoryInterface;
use Baza\Shared\Domain\Bus\Query\QueryHandler;

class GetCurrentChangesQueryHandler implements QueryHandler
{
    public function __construct(
        private ChangesRepositoryInterface $repository
    )
    {
    }

    public function __invoke(GetCurrentChangesQuery $query): ?ChangeIdResponse
    {
        $id = $this->repository->getChangeId($query->getUserId());

        if ($id === null) {
            return null;
        }

        return new ChangeIdResponse($id);
    }
}
