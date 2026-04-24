<?php

declare(strict_types=1);

namespace Tickets\Location\Application\Edit;

use Shared\Domain\Bus\Command\Command;
use Shared\Domain\ValueObject\Uuid;
use Tickets\Location\Dto\LocationDto;

class LocationEditCommand implements Command
{
    public function __construct(
        private Uuid        $id,
        private LocationDto $data,
    ) {
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getData(): LocationDto
    {
        return $this->data;
    }
}
