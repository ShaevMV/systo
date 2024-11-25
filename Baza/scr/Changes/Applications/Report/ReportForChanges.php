<?php

declare(strict_types=1);

namespace Baza\Changes\Applications\Report;

use Baza\Shared\Domain\Bus\Query\QueryBus;
use Baza\Shared\Infrastructure\Bus\Query\InMemorySymfonyQueryBus;

class ReportForChanges
{
    private QueryBus $bus;

    public function __construct(
        private ReportForChangesQueryHandler $reportForChangesQueryHandler
    )
    {
        $this->bus = new InMemorySymfonyQueryBus([
            ReportForChangesQuery::class => $this->reportForChangesQueryHandler
        ]);
    }

    public function getReport(): ReportForChangesResponse
    {
        /** @var ReportForChangesResponse $result */
        $result = $this->bus->ask(new ReportForChangesQuery());

        return $result;
    }
}
