<?php

declare(strict_types=1);

namespace Tickets\Template\Dto;

use Carbon\Carbon;
use Shared\Domain\Bus\Query\Response;
use Shared\Domain\Entity\AbstractionEntity;
use Shared\Domain\ValueObject\Uuid;
use Tickets\Template\Domain\TemplateEngine;
use Tickets\Template\Domain\TemplateKind;

/**
 * Шаблон письма/PDF. Пассивная сущность (как LocationDto): только данные + фабрика fromState.
 */
class TemplateDto extends AbstractionEntity implements Response
{
    public function __construct(
        protected Uuid $id,
        protected string $slug,
        protected string $kind,
        protected string $engine,
        protected string $title,
        protected string $body,
        protected ?string $draft_body,
        protected ?string $compiled_html,
        protected bool $active,
        protected bool $is_system,
        protected ?Carbon $created_at = null,
        protected ?Carbon $updated_at = null,
    ) {
    }

    public static function fromState(array $data): self
    {
        return new self(
            empty($data['id']) ? Uuid::random() : new Uuid($data['id']),
            $data['slug'],
            TemplateKind::isValid($data['kind'] ?? null) ? $data['kind'] : TemplateKind::EMAIL,
            TemplateEngine::isValid($data['engine'] ?? null) ? $data['engine'] : TemplateEngine::HTML,
            $data['title'] ?? $data['slug'],
            $data['body'] ?? '',
            $data['draft_body'] ?? null,
            $data['compiled_html'] ?? null,
            (bool) ($data['active'] ?? true),
            (bool) ($data['is_system'] ?? false),
            empty($data['created_at']) ? null : new Carbon($data['created_at']),
            empty($data['updated_at']) ? null : new Carbon($data['updated_at']),
        );
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getKind(): string
    {
        return $this->kind;
    }

    public function getEngine(): string
    {
        return $this->engine;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function getDraftBody(): ?string
    {
        return $this->draft_body;
    }

    public function getCompiledHtml(): ?string
    {
        return $this->compiled_html;
    }

    public function getActive(): bool
    {
        return $this->active;
    }

    public function getIsSystem(): bool
    {
        return $this->is_system;
    }

    /** Тело для рендера в прод: скомпилированный HTML (mjml) или исходный body (html). */
    public function getRenderBody(): string
    {
        return $this->compiled_html ?? $this->body;
    }

    /** created_at/updated_at не шлём в create() — их ставит Eloquent (как TicketTypeDto и пр.). */
    public function toArrayForCreate(): array
    {
        $result = $this->toArray();
        unset($result['created_at'], $result['updated_at']);

        return $result;
    }
}
