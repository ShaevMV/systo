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

    /**
     * Отчёт смен фестиваля. $festivalId — фестиваль отчёта (TD-48); null → дефолтный
     * config('baza.default_festival_id') (прежнее поведение).
     */
    public function getReport(?string $festivalId = null): ReportForChangesResponse
    {
        $festivalId = ($festivalId !== null && $festivalId !== '')
            ? $festivalId
            : (string) config('baza.default_festival_id');

        /** @var ReportForChangesResponse $result */
        $result = $this->bus->ask(new ReportForChangesQuery($festivalId));

        return $result;
    }
}
