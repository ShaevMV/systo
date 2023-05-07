<?php

declare(strict_types=1);

namespace Baza\Changes\Applications\GetCurrentChanges;

use Baza\Shared\Domain\Bus\Query\Query;

class GetCurrentChangesQuery implements Query
{
    public function __construct(
        private int $userId,
    )
    {
    }

    public function getUserId(): int
    {
        return $this->userId;
    }
}
