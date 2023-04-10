<?php

declare(strict_types=1);

namespace Baza\Tickets\Applications\Search;

use Baza\Shared\Domain\Bus\Query\Response;

interface TicketResponseInterface extends Response
{
    public function toArray(): array;

    public static function fromState(array $data): self;
}
