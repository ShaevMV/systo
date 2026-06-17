<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Application\CreateFestival;

use Shared\Domain\Bus\Command\Command;
use Tickets\Order\OrderTicket\Dto\Festival\FestivalDto;

class CreateFestivalCommand implements Command
{
    public function __construct(
        private FestivalDto $data
    ) {
    }

    public function getData(): FestivalDto
    {
        return $this->data;
    }
}
