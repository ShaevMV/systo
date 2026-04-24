<?php

declare(strict_types=1);

namespace Tickets\Location\Dto;

use Carbon\Carbon;
use Shared\Domain\Bus\Query\Response;
use Shared\Domain\Entity\AbstractionEntity;
use Shared\Domain\ValueObject\Uuid;

class LocationDto extends AbstractionEntity implements Response
{
    public function __construct(
        protected Uuid    $id,
        protected Uuid    $festival_id,
        protected string  $name,
        protected bool    $active,
        protected int     $sort,
        protected ?string $description = null,
        protected ?Carbon $created_at = null,
        protected ?Carbon $updated_at = null,
    ) {
    }

    public static function fromState(array $data): self
    {
        return new self(
            empty($data['id']) ? Uuid::random() : new Uuid($data['id']),
            new Uuid($data['festival_id']),
            $data['name'],
            (bool)($data['active'] ?? true),
            (int)($data['sort'] ?? 0),
            $data['description'] ?? null,
            empty($data['created_at']) ? null : new Carbon($data['created_at']),
            empty($data['updated_at']) ? null : new Carbon($data['updated_at']),
        );
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getFestivalId(): Uuid
    {
        return $this->festival_id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function getSort(): int
    {
        return $this->sort;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }
}
