<?php

declare(strict_types=1);

namespace Baza\Permission\Applications\CanAccess;

use Baza\Permission\Repositories\RolePermissionRepositoryInterface;
use Baza\Shared\Domain\Bus\Query\QueryHandler;

class CanAccessQueryHandler implements QueryHandler
{
    public function __construct(
        private RolePermissionRepositoryInterface $repository
    )
    {
    }

    public function __invoke(CanAccessQuery $query): CanAccessResponse
    {
        return new CanAccessResponse(
            $this->repository->can($query->getRole(), $query->getAction())
        );
    }
}
