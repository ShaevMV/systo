<?php

declare(strict_types=1);

namespace Baza\Tickets\Applications\Blacklist;

use Baza\Shared\Domain\Bus\Query\QueryHandler;
use Baza\Tickets\Repositories\BlacklistRepositoryInterface;
use Baza\Tickets\Responses\BlacklistPageResponse;

/**
 * Отдаёт порцию синка blacklist (БД только в репозитории).
 */
class GetBlacklistQueryHandler implements QueryHandler
{
    public function __construct(
        private readonly BlacklistRepositoryInterface $repository,
    ) {}

    public function __invoke(GetBlacklistQuery $query): BlacklistPageResponse
    {
        return $this->repository->page(
            $query->getFestivalId(),
            $query->getSince(),
            $query->getAfterId(),
            $query->getLimit(),
        );
    }
}
