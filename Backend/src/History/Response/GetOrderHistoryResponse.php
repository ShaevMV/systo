<?php

namespace Tickets\History\Response;

use Shared\Domain\Bus\Query\Response;
use Tickets\History\Dto\DomainHistoryDto;

class GetOrderHistoryResponse implements Response
{
    /**
     * @param DomainHistoryDto[] $list
     */
    public function __construct(
        public array $list
    )
    {
    }
}
