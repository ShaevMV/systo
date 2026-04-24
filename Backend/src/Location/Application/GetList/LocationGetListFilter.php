<?php

declare(strict_types=1);

namespace Tickets\Location\Application\GetList;

use Shared\Domain\ValueObject\Uuid;

class LocationGetListFilter
{
    public function __construct(
        private ?string $name = null,
        private ?bool   $active = null,
        private ?Uuid   $festival_id = null,
    ) {
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getActive(): ?bool
    {
        return $this->active;
    }

    public function getFestivalId(): ?Uuid
    {
        return $this->festival_id;
    }

    public static function fromState(array $data): self
    {
        $festivalId = null;
        if (!empty($data['festival_id'])) {
            $festivalId = new Uuid($data['festival_id']);
        }

        return new self(
            $data['name'] ?? null,
            ($data['active'] ?? null) === null ? null : filter_var($data['active'], FILTER_VALIDATE_BOOLEAN),
            $festivalId,
        );
    }
}
