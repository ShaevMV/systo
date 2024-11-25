<?php

declare(strict_types = 1);

namespace Tickets\User\Account\Response;

use Shared\Domain\Bus\Query\Response;
use Shared\Domain\ValueObject\Uuid;

final class IdAccountResponse implements Response
{
    public function __construct(public Uuid $id)
    {
    }
}
