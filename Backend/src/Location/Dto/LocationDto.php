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
        protected Uuid $id,
        protected string $name,
        protected ?string $description,
        protected ?Uuid $questionnaire_type_id,
        protected Uuid $festival_id,
        protected ?string $email_template,
        protected ?string $pdf_template,
        protected bool $active,
        protected ?Carbon $created_at = null,
        protected ?Carbon $updated_at = null,
    ) {
    }

    public static function fromState(array $data): self
    {
        return new self(
            empty($data['id']) ? Uuid::random() : new Uuid($data['id']),
            $data['name'],
            $data['description'] ?? null,
            empty($data['questionnaire_type_id']) ? null : new Uuid($data['questionnaire_type_id']),
            new Uuid($data['festival_id']),
            $data['email_template'] ?? null,
            $data['pdf_template'] ?? null,
            (bool) ($data['active'] ?? true),
            empty($data['created_at']) ? null : new Carbon($data['created_at']),
            empty($data['updated_at']) ? null : new Carbon($data['updated_at']),
        );
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getQuestionnaireTypeId(): ?Uuid
    {
        return $this->questionnaire_type_id;
    }

    public function getFestivalId(): Uuid
    {
        return $this->festival_id;
    }

    public function getEmailTemplate(): ?string
    {
        return $this->email_template;
    }

    public function getPdfTemplate(): ?string
    {
        return $this->pdf_template;
    }

    public function getActive(): bool
    {
        return $this->active;
    }
}
