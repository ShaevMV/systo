<?php

declare(strict_types=1);

namespace Tickets\Location\Application\Create;

use Shared\Domain\Bus\Command\Command;
use Tickets\Location\Dto\LocationDto;

class LocationCreateCommand implements Command
{
    public function __construct(
        private LocationDto $data
    ) {
    }

    public function getData(): LocationDto
    {
        return $this->data;
    }
}
