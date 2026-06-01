<?php

declare(strict_types=1);

namespace Tickets\Option\Application\Create;

use Shared\Domain\Bus\Command\Command;
use Tickets\Option\Dto\OptionDto;
use Tickets\Option\Dto\OptionTicketTypeBindingDto;

class OptionCreateCommand implements Command
{
    /**
     * @param  OptionTicketTypeBindingDto[]  $bindings
     */
    public function __construct(
        private OptionDto $data,
        private array $bindings = [],
    ) {
    }

    public function getData(): OptionDto
    {
        return $this->data;
    }

    /**
     * @return OptionTicketTypeBindingDto[]
     */
    public function getBindings(): array
    {
        return $this->bindings;
    }
}
