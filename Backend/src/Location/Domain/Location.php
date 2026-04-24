<?php

declare(strict_types=1);

namespace Tickets\Location\Domain;

use Shared\Domain\Aggregate\AggregateRoot;
use Shared\Domain\ValueObject\Uuid;
use Tickets\Location\Dto\LocationDto;

/**
 * Доменный агрегат локации фестиваля.
 */
final class Location extends AggregateRoot
{
    private function __construct(
        private Uuid    $id,
        private Uuid    $festivalId,
        private string  $name,
        private ?string $description,
        private bool    $active,
        private int     $sort,
    ) {
    }

    /**
     * Фабричный метод: создание новой локации.
     */
    public static function create(LocationDto $dto): self
    {
        return new self(
            $dto->getId(),
            $dto->getFestivalId(),
            $dto->getName(),
            $dto->getDescription(),
            $dto->isActive(),
            $dto->getSort(),
        );
    }

    /**
     * Фабричный метод: редактирование локации.
     */
    public static function edit(LocationDto $dto): self
    {
        return new self(
            $dto->getId(),
            $dto->getFestivalId(),
            $dto->getName(),
            $dto->getDescription(),
            $dto->isActive(),
            $dto->getSort(),
        );
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getFestivalId(): Uuid
    {
        return $this->festivalId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function getSort(): int
    {
        return $this->sort;
    }
}
