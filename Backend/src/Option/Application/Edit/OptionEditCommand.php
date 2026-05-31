<?php

declare(strict_types=1);

namespace Tickets\Option\Application\Edit;

use Shared\Domain\ValueObject\Uuid;
use Shared\Domain\Bus\Command\Command;
use Tickets\Option\Dto\OptionDto;
use Tickets\Option\Dto\OptionTicketTypeBindingDto;

class OptionEditCommand implements Command
{
    /**
     * @param  OptionTicketTypeBindingDto[]|null  $bindings  null = не трогаем привязки, [] = очистить все
     */
    public function __construct(
        private Uuid $id,
        private OptionDto $data,
        private ?array $bindings = null,
    ) {
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getData(): OptionDto
    {
        return $this->data;
    }

    /**
     * @return OptionTicketTypeBindingDto[]|null
     */
    public function getBindings(): ?array
    {
        return $this->bindings;
    }
}
