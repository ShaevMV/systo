<?php

declare(strict_types=1);

namespace Baza\ShiftSchedule\Applications\ListSchedules;

use Baza\Shared\Domain\Bus\Query\QueryHandler;
use Baza\ShiftSchedule\Repositories\ShiftScheduleRepositoryInterface;

class ListSchedulesQueryHandler implements QueryHandler
{
    public function __construct(
        private ShiftScheduleRepositoryInterface $repository,
    ) {
    }

    public function __invoke(ListSchedulesQuery $query): SchedulesResponse
    {
        return new SchedulesResponse(
            $this->repository->listForFestival(
                $query->getFestivalId(),
                $query->getShiftDate(),
                $query->getKppPoint(),
            )
        );
    }
}
