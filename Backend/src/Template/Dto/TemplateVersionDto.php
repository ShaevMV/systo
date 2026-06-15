<?php

declare(strict_types=1);

namespace Tickets\Template\Dto;

use Carbon\Carbon;
use Shared\Domain\Bus\Query\Response;
use Shared\Domain\Entity\AbstractionEntity;
use Shared\Domain\ValueObject\Uuid;

/**
 * Версия шаблона (снапшот опубликованного body) для списка истории и отката.
 */
class TemplateVersionDto extends AbstractionEntity implements Response
{
    public function __construct(
        protected Uuid $id,
        protected Uuid $template_id,
        protected string $body,
        protected ?string $comment,
        protected ?string $author_id,
        protected ?Carbon $created_at = null,
    ) {
    }

    public static function fromState(array $data): self
    {
        return new self(
            empty($data['id']) ? Uuid::random() : new Uuid($data['id']),
            new Uuid($data['template_id']),
            $data['body'] ?? '',
            $data['comment'] ?? null,
            $data['author_id'] ?? null,
            empty($data['created_at']) ? null : new Carbon($data['created_at']),
        );
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }
}
