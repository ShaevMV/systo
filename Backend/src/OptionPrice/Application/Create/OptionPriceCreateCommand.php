<?php

declare(strict_types=1);

namespace Tickets\OptionPrice\Application\Create;

use Shared\Domain\Bus\Command\Command;
use Tickets\OptionPrice\Dto\OptionPriceDto;

class OptionPriceCreateCommand implements Command
{
    public function __construct(
        private OptionPriceDto $data,
    ) {
    }

    public function getData(): OptionPriceDto
    {
        return $this->data;
    }
}
