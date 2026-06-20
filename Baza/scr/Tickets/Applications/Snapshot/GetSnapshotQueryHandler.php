<?php

declare(strict_types=1);

namespace Baza\Tickets\Applications\Snapshot;

use Baza\Shared\Domain\Bus\Query\QueryHandler;
use Baza\Tickets\Repositories\TicketSearchRepositoryInterface;
use Baza\Tickets\Responses\SnapshotPageResponse;

/**
 * Отдаёт порцию офлайн-снимка билетов из индекса ticket_search (БД только в репозитории).
 */
class GetSnapshotQueryHandler implements QueryHandler
{
    public function __construct(
        private readonly TicketSearchRepositoryInterface $repository,
    ) {}

    public function __invoke(GetSnapshotQuery $query): SnapshotPageResponse
    {
        return $this->repository->snapshot(
            $query->getFestivalId(),
            $query->getSince(),
            $query->getAfterId(),
            $query->getLimit(),
        );
    }
}
