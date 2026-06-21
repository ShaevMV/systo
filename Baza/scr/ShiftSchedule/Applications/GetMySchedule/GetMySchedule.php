<?php

declare(strict_types=1);

namespace Baza\ShiftSchedule\Applications\GetMySchedule;

use Baza\Shared\Domain\Bus\Query\QueryBus;
use Baza\Shared\Infrastructure\Bus\Query\InMemorySymfonyQueryBus;

/**
 * Личное расписание сотрудника (PR-A): открытые/будущие смены, где он в составе.
 * Доступно рядовому сотруднику (только своё). Тонкий слой — БД в репозитории.
 */
class GetMySchedule
{
    private QueryBus $bus;

    public function __construct(
        GetMyScheduleQueryHandler $getMyScheduleQueryHandler,
    ) {
        $this->bus = new InMemorySymfonyQueryBus([
            GetMyScheduleQuery::class => $getMyScheduleQueryHandler,
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function get(int $userId): array
    {
        /** @var MyScheduleResponse $result */
        $result = $this->bus->ask(new GetMyScheduleQuery($userId));

        return $result->getSchedules();
    }
}
