<?php

declare(strict_types=1);

namespace Baza\Tickets\Applications\Blacklist;

use Baza\Shared\Domain\Bus\Query\QueryBus;
use Baza\Shared\Infrastructure\Bus\Query\InMemorySymfonyQueryBus;
use Baza\Tickets\Repositories\BlacklistRepositoryInterface;
use Baza\Tickets\Responses\BlacklistPageResponse;

/**
 * Чёрный список отозванных билетов (Ф5, PR-6): приём отзыва (write) + синк (read via bus).
 */
class BlacklistApplication
{
    private QueryBus $bus;

    public function __construct(
        private readonly BlacklistRepositoryInterface $repository,
        GetBlacklistQueryHandler $getBlacklistQueryHandler,
    ) {
        $this->bus = new InMemorySymfonyQueryBus([
            GetBlacklistQuery::class => $getBlacklistQueryHandler,
        ]);
    }

    /** Отозвать билет (приём от org через ingest). */
    public function revoke(?string $ticketUuid, ?int $kilter, ?string $festivalId, ?string $reason): bool
    {
        return $this->repository->revoke($ticketUuid, $kilter, $festivalId, $reason);
    }

    public function getPage(GetBlacklistQuery $query): BlacklistPageResponse
    {
        /** @var BlacklistPageResponse $result */
        $result = $this->bus->ask($query);

        return $result;
    }
}
