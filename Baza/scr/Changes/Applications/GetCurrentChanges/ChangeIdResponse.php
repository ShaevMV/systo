<?php

declare(strict_types=1);

namespace Baza\Changes\Applications\GetCurrentChanges;

use Baza\Shared\Domain\Bus\Query\Response;

class ChangeIdResponse implements Response
{
    public function __construct(
        private int $changeId,
    )
    {
    }

    public function getChangeId(): int
    {
        return $this->changeId;
    }
}
