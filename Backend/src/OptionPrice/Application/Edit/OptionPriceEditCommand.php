<?php

declare(strict_types=1);

namespace Tickets\OptionPrice\Application\Edit;

use Shared\Domain\Bus\Command\Command;
use Shared\Domain\ValueObject\Uuid;
use Tickets\OptionPrice\Dto\OptionPriceDto;

class OptionPriceEditCommand implements Command
{
    public function __construct(
        private Uuid $id,
        private OptionPriceDto $data,
    ) {
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getData(): OptionPriceDto
    {
        return $this->data;
    }
}
