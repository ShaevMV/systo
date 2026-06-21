<?php

declare(strict_types=1);

namespace Baza\ShiftSchedule\Applications\GetMySchedule;

use Baza\Shared\Domain\Bus\Query\QueryHandler;
use Baza\ShiftSchedule\Repositories\ShiftScheduleRepositoryInterface;

class GetMyScheduleQueryHandler implements QueryHandler
{
    public function __construct(
        private ShiftScheduleRepositoryInterface $repository,
    ) {
    }

    public function __invoke(GetMyScheduleQuery $query): MyScheduleResponse
    {
        return new MyScheduleResponse(
            $this->repository->getMySchedule($query->getUserId())
        );
    }
}
