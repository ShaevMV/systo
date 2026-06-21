<?php

declare(strict_types=1);

namespace Baza\ShiftSchedule\Applications\ListSchedules;

use Baza\Shared\Domain\Bus\Query\QueryBus;
use Baza\Shared\Infrastructure\Bus\Query\InMemorySymfonyQueryBus;

/**
 * Список плановых смен фестиваля для сетки (PR-A). Тонкий слой — БД в репозитории.
 * Стиль по образцу Baza\Changes\Applications\GetCurrentChanges\GetCurrentChanges.
 */
class ListSchedules
{
    private QueryBus $bus;

    public function __construct(
        ListSchedulesQueryHandler $listSchedulesQueryHandler,
    ) {
        $this->bus = new InMemorySymfonyQueryBus([
            ListSchedulesQuery::class => $listSchedulesQueryHandler,
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function list(string $festivalId, ?string $shiftDate = null, ?string $kppPoint = null): array
    {
        /** @var SchedulesResponse $result */
        $result = $this->bus->ask(new ListSchedulesQuery($festivalId, $shiftDate, $kppPoint));

        return $result->getSchedules();
    }
}
