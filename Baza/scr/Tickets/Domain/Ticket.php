<?php

declare(strict_types=1);

namespace Baza\Tickets\Domain;

use Baza\Tickets\ValueObject\Color;
use Carbon\Carbon;

abstract class Ticket
{
    protected Color $color;

    public function __construct(
        protected int $kilter,
        protected ?int $change_id = null,
        protected ?Carbon $date_change = null
    ){
        $this->color = $this->getColor();
    }

    abstract protected function getColor(): Color;

    abstract public static function fromState(array $data): self;
}
