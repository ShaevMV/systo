<?php

declare(strict_types=1);

namespace Baza\ShiftSchedule\Applications\GetMySchedule;

use Baza\Shared\Domain\Bus\Query\Query;

class GetMyScheduleQuery implements Query
{
    public function __construct(
        private int $userId,
    ) {
    }

    public function getUserId(): int
    {
        return $this->userId;
    }
}
