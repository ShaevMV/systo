<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Application\Edit;

use Shared\Domain\Bus\Command\Command;
use Shared\Domain\ValueObject\Uuid;
use Tickets\Order\OrderTicket\Dto\Festival\FestivalDto;

class FestivalEditCommand implements Command
{
    public function __construct(
        private Uuid $id,
        private FestivalDto $data,
    ) {
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getData(): FestivalDto
    {
        return $this->data;
    }
}
