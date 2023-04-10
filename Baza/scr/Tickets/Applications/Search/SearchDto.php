<?php

declare(strict_types=1);

namespace Baza\Tickets\Applications\Search;

use Baza\Shared\Domain\ValueObject\Uuid;

class SearchDto
{
    public function __construct(
        private string   $type,
        private int|Uuid $id,
    )
    {
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getId(): Uuid|int
    {
        return $this->id;
    }

    public function getIdToString(): string
    {
        if ($this->id instanceof Uuid) {
            return $this->id->value();
        }

        return DefineService::PREFIX_LIST[$this->type] . $this->id;
    }
}
