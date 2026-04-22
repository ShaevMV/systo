<?php

declare(strict_types=1);

namespace Tickets\TicketType\Dto;

use Shared\Domain\Entity\AbstractionEntity;
use Shared\Domain\ValueObject\Uuid;

class FestivalDto extends AbstractionEntity
{
    public function __construct(
        protected ?Uuid $id = null,
        protected ?string $name = null,
        protected ?string $description = null,
        protected ?string $email = null,
        protected ?string $pdf = null,
    )
    {
    }

    public static function fromState(array $data): self
    {
        return new self(
            !empty($data['festival_id']) ? new Uuid($data['festival_id']) : null,
            $data['festival_name'] ?? null,
            $data['festival_description'] ?? null,
            $data['festival_email'] ?? null,
            $data['festival_pdf'] ?? null,
        );
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getPdf(): ?string
    {
        return $this->pdf;
    }
}
