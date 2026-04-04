<?php

declare(strict_types=1);

namespace Tickets\QuestionnaireType\Dto;

use Carbon\Carbon;
use Shared\Domain\Bus\Query\Response;
use Shared\Domain\Entity\AbstractionEntity;
use Shared\Domain\ValueObject\Uuid;

class QuestionnaireTypeDto extends AbstractionEntity implements Response
{
    public function __construct(
        protected Uuid $id,
        protected string $name,
        protected ?string $code,
        protected array $questions,
        protected bool $active,
        protected int $sort,
        protected ?Carbon $created_at = null,
        protected ?Carbon $updated_at = null,
    )
    {
    }

    public static function fromState(array $data): self
    {
        return new self(
            empty($data['id']) ? Uuid::random() : new Uuid($data['id']),
            $data['name'],
            $data['code'] ?? null,
            $data['questions'] ?? [],
            (bool)($data['active'] ?? true),
            (int)($data['sort'] ?? 0),
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

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function getQuestions(): array
    {
        return $this->questions;
    }

    public function getActive(): bool
    {
        return $this->active;
    }

    public function getSort(): int
    {
        return $this->sort;
    }
}
