<?php

declare(strict_types = 1);

namespace Tickets\User\Response;

use Tickets\Shared\Domain\Bus\Query\Response;
use Tickets\Shared\Domain\ValueObject\Uuid;

final class IdAccountResponse implements Response
{
    public function __construct(public Uuid $id)
    {
    }
}
